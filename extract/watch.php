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
}

class Watcher {
	public $conf;

	function start() {
		$this->conf = New Conf;
	}
}

$watcher = New Watcher;
$watcher->start();


$identity = "/home/user/.ssh/id_rsa_foxrep";
$server = "easysadmin@foxrep.nat.internal";
$dumps_path = '/data01/datadump/*.tgz';
$dump_path_check_regex = "/^\/data01\/datadump\/FoxtrotTableDump[0-9]{8,8}\.tgz$/";

/*
$identity = "";
$server = "golf@eacbr-db1.nat.internal";
$dumps_path = '/var/golf/foxtrot_dump/*201101*.tgz';
$dump_path_check_regex = "/^\/var\/golf\/foxtrot_dump\/FoxtrotTableDump[0-9]{8,8}\.tgz$/";
*/

$remote_command = 'date +%s; for file in '.escapeshellarg($dumps_path).' ; do echo $file; stat --format=%Y $file; md5sum $file | cut -d " " -f 1; done';
$dump_query = trim(shell_exec("ssh -i ".escapeshellarg($identity)." ".escapeshellarg($server)." '".$remote_command."'"));

if (empty($dump_query)) {
	die("Y U NO connect");
}

$dump_query_rows = explode("\n", $dump_query);

$remote_time = array_shift($dump_query_rows);

if (preg_match("/^[0-9]+$/", $remote_time) !== 1) {
	die("Y U NO remote time");
}

$local_time = time();

$time_difference = $local_time - $remote_time;

//$time_difference = $local_time - $remote_time;
//$local_time = $remote_time + $time_difference;
//$remote_time = $local_time - $time_difference;

if (abs($time_difference) > 86400) {
	die("Y U NO clock");
}

/* print_r($dump_query_rows); */

/* var_dump(!empty($dump_query_rows)); */
/* var_dump(count($dump_query_rows) % 3 === 0); */

foreach ($dump_query_rows as $row_count => $dump_query_row) {
	//1st row
	if ($row_count % 3 === 0) {
		if (preg_match($dump_path_check_regex, $dump_query_row) !== 1) {
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

unset($row_count);
unset($files);

for ($row_count = 0; $row_count < count($dump_query_rows); $row_count += 3) {
	$file_path = $dump_query_rows[$row_count + 0];
	$file_modtime_remotetime = $dump_query_rows[$row_count + 1];
	$file_md5 = $dump_query_rows[$row_count + 2];

	$file_modtime_localtime = $file_modtime_remotetime + $time_difference;

	$files[] = array("path" => $file_path, "modtime" => $file_modtime_localtime, "md5" => $file_md5);

	$file_dates[] = $file_modtime_localtime;
}

array_multisort($file_dates, SORT_DESC, $files);

/* array_shift($files); */

/* print_r($files); */

foreach ($files as $file_key => $file) {
/* 	var_dump(date("r", $file['modtime'])); */
	var_dump($file['path']);

	if (false) continue;

	$newest_process_query = runq("SELECT max(source_timestamp) FROM processes;");
	$newest_process_timestamp = $newest_process_query[0]['max'];

	print_r(strtotime($newest_process_timestamp));

	if ($file['modtime'] <= strtotime($newest_process_timestamp)) {
		var_dump("skipping");
	}

	$already_processed_query = runq("SELECT count(*) FROM processes WHERE source_md5='".pg_escape_string($file['md5'])."' OR source_timestamp='".pg_escape_string(date("c", $file['modtime']))."';");
	$already_processed_count = $already_processed_query[0]['count'];

	if ($already_processed_count > 0) {
		var_dump("skipping");
	}

	var_dump("jackpot");

	$process_id_query = runq("SELECT nextval('processes_process_id_seq');");
	$process_id = $process_id_query[0]['nextval'];

	runq("INSERT INTO processes (process_id, source_path, source_timestamp, source_md5, watch_pid) VALUES ('".pg_escape_string($process_id)."', '".pg_escape_string($file['path'])."', '".pg_escape_string(date("c", $file['modtime']))."', '".pg_escape_string($file['md5'])."', '".pg_escape_string($pid)."');");

	mkdir("/home/user/hotel/extract_processes/".$process_id);

	system("/home/user/hotel/extract.sh ".escapeshellarg($process_id)." ".escapeshellarg($file['path']));

	break;
}

?>