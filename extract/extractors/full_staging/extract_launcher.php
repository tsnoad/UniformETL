<?php

/**
 * Extract Process Launcher
 *
 * Looks for new data to process. This script is run every minute by the
 * extract daemon. It checks for new tar files on the target server, and when
 * it finds one it checks that it's not still being modified, that we havn't
 * already processed it, and that it's not older than the last file we
 * processed. Then, if everything's okay the file is processed.
 *
 */

require_once("/etc/uniformetl/autoload.php");
require_once("/etc/uniformetl/database.php");

class ExtractFullStagingLauncher {
	function start() {
		//helpful log message
		echo "Starting Launcher...\n";
		echo "\tChecking environment...\t";

		//make sure nothing else is running
		$this->check_already_extracting();
		$this->check_already_transforming();

		//helpful log message
		echo "OK\n";

		//get all the files on the remote server
		$dump_query_rows = $this->list_remote_dumps();

		//what's the difference between clocks on the local and remote servers
		//also remove the remote timestamp from the array
		list($dump_query_rows, $time_difference) = $this->calc_time_difference($dump_query_rows);

		//make sure the remote file list is valid
		$this->check_list_format($dump_query_rows);

		//modify the remote file list into an array we can use
		$files = $this->create_dump_array($dump_query_rows, $time_difference);

		//loop through all the files we found on the remote server
		foreach ($files as $file_key => $file) {
			//helpful log message
			echo "\n\t\tFound: \t".basename($file['path'])."\n";
			echo "\t\tmtime: \t".date("r", $file['modtime'])."\n";
			echo "\t\thash: \t".$file['md5']."\n";

			try {
				//is the file too new (may be it's still being written)
				$this->dump_too_new($file);
				//have we already processed the file
				$this->dump_already_processed($file);
				//is the file too old - older than any other file we've already processed
				$this->dump_too_old($file);
			} catch (Exception $e) {
				print_r($e->getMessage());
				continue;
			}

			//start the extract on the file
			$this->start_extract($file);

			//helpful log message
			print_r("dump started");

			//we only want to start one extract
			return;
		}
	}

	/*
	 * Check if the laucher is already running, or if there's an extract in progress
	 */
	function check_already_extracting() {
		//is the laucher running more than once (once being us)
		if (trim(shell_exec("ps h -C run_extract_launcher.php o pid | wc -l")) > 1) {
			//let's not step on our own toes
			die("extract launcher is currently running");
		}

		//check if there are any extract processes running at the moment
		$already_extracting = runq("SELECT count(*) as count FROM extract_processes WHERE finished=FALSE;");

		//we'll have to wait until the extract finishes
		if ($already_extracting[0]['count'] > 0) {
			die("extract is currently running\n");
		}
	}

	/*
	 * Check if there's a transform in progress
	 */
	function check_already_transforming() {
		//check if there are any transform processes running at the moment
		$already_transforming = runq("SELECT count(*) as count FROM transform_processes WHERE finished=FALSE;");

		//we'll have to wait until the transform finishes
		if ($already_transforming[0]['count'] > 0) {
			die("transform is currently running\n");
		}
	}

	/*
	 * Get information about all the dump files that are on the remote server
	 * Also, get the current time of the remote server
	 */
	function list_remote_dumps() {
		//helpful log message
		echo "\tSearching for files...\t";

		//this is the command we want to run on the remote server
		//get the time
		$remote_command = 'date +%s; ';
		//find all matching files and get the path/name, mtime, and md5 hash of each
		$remote_command .= 'for file in '.escapeshellarg(Conf::$dumps_path).' ; do echo $file; stat --format=%Y $file; md5sum $file | cut -d " " -f 1; done';

		//run the command on the remote server
		$dump_query = shell_exec("ssh -i ".escapeshellarg(Conf::$identity)." ".escapeshellarg(Conf::$server)." '".$remote_command."'");

		//trim the trailing newline
		$query_result = trim($dump_query);
		
		//nothing returned?
		if (empty($query_result)) {
			die("Y U NO connect");
		}

		//split lines into array
		return explode("\n", $query_result);
	}

	/*
	 * What's the time difference between the local and remote servers
 	 */
	function calc_time_difference($dump_query_rows) {
		//the remote timestamp is the first line returned from the command we just ran on the remote server
		//remove the timestamp from the array
		$remote_time = array_shift($dump_query_rows);
		
		//make sure it looks like a valid timestamp
		if (preg_match("/^[0-9]+$/", $remote_time) !== 1) {
			throw new Exception("Y U NO remote time");
		}
		
		//what's the time on the local server
		$local_time = time();
		
		//what's the difference
		$time_difference = $local_time - $remote_time;
		
		//conversion matrix
		//$time_difference = $local_time - $remote_time;
		//$local_time = $remote_time + $time_difference;
		//$remote_time = $local_time - $time_difference;
		
		//if the difference is more than 24 hours
		if (abs($time_difference) > 86400) {
			//then something's wrong
			throw new Exception("Y U NO clock");
		}

		//return the timestamp
		//and the array of remote file data with the timestamp removed
		return array($dump_query_rows, $time_difference);
	}

	/*
	 * Make sure the list of remote files is valid-looking
	 */
	function check_list_format($dump_query_rows) {
		//loop through all the rows
		foreach ($dump_query_rows as $row_count => $dump_query_row) {
			//1st row
			if ($row_count % 3 === 0) {
				//should be a file path/name
				if (preg_match(Conf::$dump_path_check_regex, $dump_query_row) !== 1) {
					throw new Exception("Y U NO filepath");
				}
		
			//2nd row
			} else if ($row_count % 3 === 1) {
				//should be a timestamp
				if (preg_match("/^[0-9]+$/", $dump_query_row) !== 1) {
					throw new Exception("Y U NO timestamp");
				}
		
			//3rd row
			} else if ($row_count % 3 === 2) {
				//should be a md5 hash
				if (preg_match("/^[0-9a-z]{32,32}$/", $dump_query_row) !== 1) {
					throw new Exception("Y U NO md5");
				}
			}
		}
	}

	/*
	 * Take the list of remote files and turn it into an array that we can use
	 */
	function create_dump_array($dump_query_rows, $time_difference) {
		//each file has three rows in the list
		//loop through every three rows
		for ($row_count = 0; $row_count < count($dump_query_rows); $row_count += 3) {
			//1st row: file path/name
			$file_path = $dump_query_rows[$row_count + 0];
			//2nd row: file mtime
			$file_modtime_remotetime = $dump_query_rows[$row_count + 1];
			//3rd row: file hash
			$file_md5 = $dump_query_rows[$row_count + 2];
		
			//the file mtime is in the remote server's time
			//we need to convert it to local time
			$file_modtime_localtime = $file_modtime_remotetime + $time_difference;
		
			//add the file's information to an array
			$files[] = array("path" => $file_path, "modtime" => $file_modtime_localtime, "md5" => $file_md5);
		
			//create a second array of modtimes
			//we can use this to sort the files by modtime
			$file_dates[] = $file_modtime_localtime;
		}
		
		//sort the files by modtime
		//newest files first
		array_multisort($file_dates, SORT_DESC, $files);

		return $files;
	}

	/*
	 * Reject files that have been modified in the last 5 minutes: they're probably still being written
	 */
	function dump_too_new($file) {
		//if the file's been modified in the last 5 minutes
		if ($file['modtime'] + 300 > time()) {
			throw new Exception("skipping - dump too new, possibly incomplete\n");
		}
	}

	/*
	 * Reject files that we've already processed
	 */
	function dump_already_processed($file) {
		//search for extract processes that have used files with the same mtime or md5 hash
		$already_processed_query = runq("SELECT count(*) AS count FROM extract_processes p INNER JOIN extract_full_staging f ON (f.extract_id=p.extract_id) WHERE f.source_md5='".db_escape($file['md5'])."' OR f.source_timestamp='".db_escape(date("c", $file['modtime']))."';");
		$already_processed_count = $already_processed_query[0]['count'];
	
		//if any extracts have used this file
		if ($already_processed_count > 0) {
			throw new Exception("skipping - already processed\n");
		}
	}

	/*
	 * Reject files that are older then the last extract
	 */
	function dump_too_old($file) {
		//what's the mtime of the newest file used for an extract
		$newest_process_query = runq("SELECT max(source_timestamp) as max FROM extract_full_staging;");
		$newest_process_timestamp = $newest_process_query[0]['max'];
	
		//if this file's mtime is older (or the same)
		if ($file['modtime'] <= strtotime($newest_process_timestamp)) {
			throw new Exception("skipping - dump too old\n");
		}
	}

	/*
	 * Start an extract process using a file we've found
	 */
	function start_extract($file) {
		//helpful log message
		var_dump("jackpot");

		//start the extract process
		shell_exec(Conf::$software_path."extract/extractors/full_staging/run_extract.php ".escapeshellarg($file['path'])." ".escapeshellarg(date("c", $file['modtime']))." ".escapeshellarg($file['md5'])." > ".Conf::$software_path."logs/extractlog &");
	}
}

?>