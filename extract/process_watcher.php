#!/usr/bin/php5
<?php

function runq($query) {
	$conn = pg_connect("dbname=hotel user=user");
	$result = pg_query($conn, $query);
	$return = pg_fetch_all($result);
	pg_close($conn);

	return $return;
}

var_dump(function_exists("proc_get_status"));

/* $unfinisheds_query = runq("SELECT * FROM extract_processes WHERE finished=FALSE;"); */

/*
if (empty($unfinisheds_query)) {
	die("no active extract processes");
}
*/

/* foreach ($unfinisheds_query as $unfinished) { */
foreach (array(array("extract_pid" => 2795), array("extract_pid" => 2649273930)) as $unfinished) {
	$unfinished_status = shell_exec("ps h p ".escapeshellarg($unfinished['extract_pid'])." o pid | wc -l");

var_dump(shell_exec("ps h p ".escapeshellarg($unfinished['extract_pid'])));
var_dump(shell_exec("ps h p ".escapeshellarg($unfinished['extract_pid'])." o pid | wc -l"));

/* 	var_dump($unfinished_status_query); */

	if (trim($unfinished_status) > 0) {
		//process is running, all is well
		continue;
	}

	sleep(5);

	$unfinished_status = shell_exec("ps h p ".escapeshellarg($unfinished['extract_pid'])." o pid | wc -l");

var_dump(shell_exec("ps h p ".escapeshellarg($unfinished['extract_pid'])));
var_dump(shell_exec("ps h p ".escapeshellarg($unfinished['extract_pid'])." o pid | wc -l"));

	if (trim($unfinished_status) > 0) {
/* 		runq("UPDATE extract_processes SET (finished=TRUE, finished_time=now(), failed=TRUE);"); */
	}
}

?>