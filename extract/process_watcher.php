#!/usr/bin/php5
<?php

/**
 * Extract Process Watcher
 *
 * Cleans up old processes. This script is run every minute by the
 * extract daemon. Finds processes that have died or have been terminated and
 * marks them as failed in the database.
 *
 */

//get config settings and database
require_once("/etc/uniformetl/autoload.php");
require_once("/etc/uniformetl/database.php");;

//helpful log message
echo "Starting Watcher...\n";

//helpful log message
echo "\tChecking environment...\t";

//check that we're not already running
if (trim(shell_exec("ps h -C process_watcher.php o pid | wc -l")) > 1) {
	//so we don't step on our own toes
	die("FAILED: watcher is already running");
}

//helpful log message
echo "OK\n";

//helpful log message
echo "\tSearching for unfinished processes...\t";

//are there any extract processes that think they're still running?
$unfinisheds_query = runq("SELECT * FROM extract_processes WHERE finished=FALSE;");

if (empty($unfinisheds_query)) {
	die("no active extract processes");
}

echo "Found ".count($unfinisheds_query)." processes\n";

//for each process that we found
foreach ($unfinisheds_query as $unfinished) {
	echo "Checking process:\n";

	//check it's pid. is it actually running
	$unfinished_status = shell_exec("ps h p ".escapeshellarg($unfinished['extract_pid'])." o pid | wc -l");

	if (trim($unfinished_status) > 0) {
		//process is running, all is well
		echo "\t"."running\n";
		continue;
	}

	//process is no longer running
	//it may have finished normally, but hasn't updated the database yet
	echo "\t"."stopped. wait 5s...\n";

	//wait a moment
	sleep(5);

	//check if the process has been marked as finished 
	$status_query = runq("SELECT * FROM extract_processes WHERE process_id='".pg_escape_string($unfinished['process_id'])."' LIMIT 1;");

	if ($status_query[0]['finished'] == 't') {
		//process has completed normally
		echo "\t"."completed.\n";
		continue;

	} else {
		echo "\t"."failed.\n";
		shell_exec(Conf::$software_path."extract/process_recorder.php failed ".escapeshellarg($unfinished['process_id']));
		continue;
	}
}

?>