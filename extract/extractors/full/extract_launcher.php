#!/usr/bin/php5
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

class ExtractFullLauncher {
	function start() {
		echo "Starting Launcher...\n";

		$this->check_already_extracting();
		$this->check_already_transforming();

		$this->list_remote_dumps();

		$this->calc_time_difference();

		$this->check_list_format();

		$this->create_dump_array();

		$this->inspect_dumps();
	}

	function check_already_extracting() {
		if (trim(shell_exec("ps h -C extract_launcher.php o pid | wc -l")) > 1) {
			die("extract launcher is currently running");
		}

		$already_extracting = runq("SELECT count(*) FROM extract_processes WHERE finished=FALSE;");

		if ($already_extracting[0]['count'] > 0) {
			die("extract is currently running");
		}
	}

	function check_already_transforming() {
		$already_transforming = runq("SELECT count(*) FROM transform_processes WHERE finished=FALSE;");

		if ($already_transforming[0]['count'] > 0) {
			die("transform is currently running");
		}
	}

	function list_remote_dumps() {
		echo "\tSearching for files...\t";

		$remote_command = 'date +%s; for file in '.escapeshellarg(Conf::$dumps_path).' ; do echo $file; stat --format=%Y $file; md5sum $file | cut -d " " -f 1; done';

		$dump_query = shell_exec("ssh -i ".escapeshellarg(Conf::$identity)." ".escapeshellarg(Conf::$server)." '".$remote_command."'");

		$query_result = trim($dump_query);
		
		if (empty($query_result)) {
			die("Y U NO connect");
		}
		
		$this->dump_query_rows = explode("\n", $query_result);
	}

	function calc_time_difference() {
		$remote_time = array_shift($this->dump_query_rows);
		
		if (preg_match("/^[0-9]+$/", $remote_time) !== 1) {
			die("Y U NO remote time");
		}
		
		$local_time = time();
		
		$this->time_difference = $local_time - $remote_time;
		
		//conversion matrix
		//$time_difference = $local_time - $remote_time;
		//$local_time = $remote_time + $time_difference;
		//$remote_time = $local_time - $time_difference;
		
		if (abs($this->time_difference) > 86400) {
			die("Y U NO clock");
		}
	}

	function check_list_format() {
		foreach ($this->dump_query_rows as $row_count => $dump_query_row) {
			//1st row
			if ($row_count % 3 === 0) {
				if (preg_match(Conf::$dump_path_check_regex, $dump_query_row) !== 1) {
					die("Y U NO filepath");
				}
		
			//2nd row
			} else if ($row_count % 3 === 1) {
				if (preg_match("/^[0-9]+$/", $dump_query_row) !== 1) {
					die("Y U NO timestamp");
				}
		
			//3rd row
			} else if ($row_count % 3 === 2) {
				if (preg_match("/^[0-9a-z]{32,32}$/", $dump_query_row) !== 1) {
					die("Y U NO md5");
				}
			}
		}
	}

	function create_dump_array() {
		for ($row_count = 0; $row_count < count($this->dump_query_rows); $row_count += 3) {
			$file_path = $this->dump_query_rows[$row_count + 0];
			$file_modtime_remotetime = $this->dump_query_rows[$row_count + 1];
			$file_md5 = $this->dump_query_rows[$row_count + 2];
		
			$file_modtime_localtime = $file_modtime_remotetime + $this->time_difference;
		
			$this->files[] = array("path" => $file_path, "modtime" => $file_modtime_localtime, "md5" => $file_md5);
		
			$file_dates[] = $file_modtime_localtime;
		}
		
		array_multisort($file_dates, SORT_DESC, $this->files);
	}

	function inspect_dumps() {
		echo "found ".count($this->files)."\n";

		foreach ($this->files as $file_key => $file) {
			echo "\n\t\tFound: \t".basename($file['path'])."\n";
			echo "\t\tmtime: \t".date("r", $file['modtime'])."\n";
			echo "\t\thash: \t".$file['md5']."\n";

			if ($this->dump_too_new($file)) {
				continue;
			}

			if (!$this->dump_already_processed($file) && !$this->dump_too_old($file)) {
				$this->start_extract($file);
			}
		}
	}

	function dump_too_new($file) {
		if ($file['modtime'] + 300 > time()) {
			var_dump("skipping - dump too new, possibly incomplete");
			return true;
		}
	}

	function dump_already_processed($file) {
		$already_processed_query = runq("SELECT count(*) FROM extract_processes p INNER JOIN extract_full f ON (f.process_id=p.process_id) WHERE f.source_md5='".pg_escape_string($file['md5'])."' OR f.source_timestamp='".pg_escape_string(date("c", $file['modtime']))."';");
		$already_processed_count = $already_processed_query[0]['count'];
	
		if ($already_processed_count > 0) {
			var_dump("skipping - already processed");
			return true;
		}
	}

	function dump_too_old($file) {
		$newest_process_query = runq("SELECT max(source_timestamp) FROM extract_full;");
		$newest_process_timestamp = $newest_process_query[0]['max'];
	
		if ($file['modtime'] <= strtotime($newest_process_timestamp)) {
			var_dump("skipping - dump too old");
			return true;
		}
	}

	function start_extract($file) {
		var_dump("jackpot");
	
		$process_id_query = runq("SELECT nextval('processes_process_id_seq');");
		$process_id = $process_id_query[0]['nextval'];
	
		runq("INSERT INTO processes (process_id) VALUES ('".pg_escape_string($process_id)."');");

		shell_exec(Conf::$software_path."extract/extractors/full/extract.sh ".escapeshellarg($process_id)." ".escapeshellarg($file['path'])." ".escapeshellarg(date("c", $file['modtime']))." ".escapeshellarg($file['md5'])." > ".Conf::$software_path."logs/extractlog 2>".Conf::$software_path."logs/extractlog & echo $!");

		die("dump started");
	}
}

$launcher = New ExtractFullLauncher;
$launcher->start();


?>