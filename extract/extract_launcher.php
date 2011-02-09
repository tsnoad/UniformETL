#!/usr/bin/php5
<?php

$pid = getmypid();

function runq($query) {
	$conn = pg_connect("dbname=hotel user=user");
	$result = pg_query($conn, $query);
	$return = pg_fetch_all($result);
	pg_close($conn);

	return $return;
}

class Conf {
	public $server = "easysadmin@foxrep.nat.internal";
	public $identity = "/home/user/.ssh/id_rsa_foxrep";
	public $dumps_path = '/data01/datadump/*.tgz';
	public $dump_path_check_regex = "/^\/data01\/datadump\/FoxtrotTableDump[0-9]{8,8}\.tgz$/";

/*
	public $identity = "";
	public $server = "golf@eacbr-db1.nat.internal";
	public $dumps_path = '/var/golf/foxtrot_dump/*201101*.tgz';
	public $dump_path_check_regex = "/^\/var\/golf\/foxtrot_dump\/FoxtrotTableDump[0-9]{8,8}\.tgz$/";
*/
}

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
		
		if (empty($dump_query)) {
			die("Y U NO connect");
		}
		
		$this->dump_query_rows = explode("\n", $dump_query);
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
			if (!$this->dump_too_old() && !$this->dump_already_processed()) {
				$this->start_extract();
			}
		}
	}

	function dump_too_old() {
		$newest_process_query = runq("SELECT max(source_timestamp) FROM extract_processes;");
		$newest_process_timestamp = $newest_process_query[0]['max'];
	
		if ($file['modtime'] <= strtotime($newest_process_timestamp)) {
			var_dump("skipping");
			return true;
		}
	}

	function dump_already_processed() {
		$already_processed_query = runq("SELECT count(*) FROM processes WHERE extract_source_md5='".pg_escape_string($file['md5'])."' OR source_timestamp='".pg_escape_string(date("c", $file['modtime']))."';");
		$already_processed_count = $already_processed_query[0]['count'];
	
		if ($already_processed_count > 0) {
			var_dump("skipping");
			return true;
		}
	}

	function start_extract() {
		var_dump("jackpot");
	
/*
		$process_id_query = runq("SELECT nextval('processes_process_id_seq');");
		$process_id = $process_id_query[0]['nextval'];
	
		runq("INSERT INTO processes (process_id, source_path, source_timestamp, source_md5, watch_pid) VALUES ('".pg_escape_string($process_id)."', '".pg_escape_string($file['path'])."', '".pg_escape_string(date("c", $file['modtime']))."', '".pg_escape_string($file['md5'])."', '".pg_escape_string($pid)."');");
	
		mkdir("/home/user/hotel/extract/extract_processes/".$process_id);
	
		system("/home/user/hotel/extract/extract.sh ".escapeshellarg($process_id)." ".escapeshellarg($file['path']));
*/
	
		die("dump started");
	}
}

$watcher = New Watcher;
$watcher->start();


?>