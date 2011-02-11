#!/usr/bin/php5
<?php

require("/etc/uniformetl/config.php");
require("/etc/uniformetl/database.php");

class Watcher {
	public $conf;

	function start() {
		$this->conf = New Conf;

		$this->list_remote_dumps();

		$this->calc_time_difference();

		$this->check_list_format();

		$this->create_dump_array();

		$this->inspect_dumps();
	}

	function list_remote_dumps() {
		$remote_command = 'date +%s; for file in '.escapeshellarg($this->conf->dumps_path).' ; do echo $file; stat --format=%Y $file; md5sum $file | cut -d " " -f 1; done';

		$dump_query = shell_exec("ssh -i ".escapeshellarg($this->conf->identity)." ".escapeshellarg($this->conf->server)." '".$remote_command."'");

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
				if (preg_match($this->conf->dump_path_check_regex, $dump_query_row) !== 1) {
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
		foreach ($this->files as $file_key => $file) {
			if (!$this->dump_already_processed($file) && !$this->dump_too_old($file)) {
				$this->start_extract($file);
			}
		}
	}

	function dump_already_processed($file) {
		$already_processed_query = runq("SELECT count(*) FROM extract_processes WHERE source_md5='".pg_escape_string($file['md5'])."' OR source_timestamp='".pg_escape_string(date("c", $file['modtime']))."';");
		$already_processed_count = $already_processed_query[0]['count'];
	
		if ($already_processed_count > 0) {
			var_dump("skipping - already processed");
			return true;
		}
	}

	function dump_too_old($file) {
		$newest_process_query = runq("SELECT max(source_timestamp) FROM extract_processes;");
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

/* 		system("/home/user/hotel/extract/extract.sh ".escapeshellarg($process_id)." ".escapeshellarg($file['path'])." ".escapeshellarg(date("c", $file['modtime']))." ".escapeshellarg($file['md5'])); */
		shell_exec("/home/user/hotel/extract/extract.sh ".escapeshellarg($process_id)." ".escapeshellarg($file['path'])." ".escapeshellarg(date("c", $file['modtime']))." ".escapeshellarg($file['md5'])." > /home/user/hotel/logs/extractlog 2>/home/user/hotel/logs/extractlog & echo $!");

		die("dump started");
	}
}

$watcher = New Watcher;
$watcher->start();


?>