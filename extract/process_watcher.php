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
require_once("/etc/uniformetl/database.php");

//helpful log message
echo "Starting Watcher...\n";

//helpful log message
echo "\tChecking environment...\t";

//check that we're not already running
if (trim(shell_exec("ps h -C process_watcher.php o pid | wc -l")) > 1) {
	//so we don't step on our own toes
	die("FAILED: watcher is already running\n");
}

//helpful log message
echo "OK\n";

//helpful log message
echo "\tSearching for unfinished processes...";

//are there any extract processes that think they're still running?
$unfinisheds_query = runq("SELECT * FROM extract_processes WHERE finished=FALSE;");

if (empty($unfinisheds_query)) {
	die("\tno active extract processes\n");
}

echo "\t".count($unfinisheds_query)." process".(count($unfinisheds_query) === 1 ? "" : "es")." found\n";

//for each process that we found
foreach ($unfinisheds_query as $unfinished) {
	echo "\t\t"."extract #{$unfinished['extract_id']} ({$unfinished['extract_pid']}):";

	//check it's pid. is it actually running
	$unfinished_status = shell_exec("ps h p ".escapeshellarg($unfinished['extract_pid'])." o pid | wc -l");

	if (trim($unfinished_status) > 0) {
		//process is running, all is well
		echo "\t"."active\n";
		continue;
	}

	//process is no longer running
	//it may have finished normally, but hasn't updated the database yet
	echo "\t"."stopped. wait 5s...\n";

	//wait a moment
	sleep(5);

	//check if the process has been marked as finished 
	$status_query = runq("SELECT * FROM extract_processes WHERE extract_id='".db_escape($unfinished['extract_id'])."' LIMIT 1;");

	if ($status_query[0]['finished'] == 't') {
		//process has completed normally
		echo "\t"."completed.\n";
		continue;

	} else {
		echo "\t"."failed.\n";

		try {
			copy(Conf::$software_path."logs/extractlog", Conf::$software_path."logs/archive/extract".$unfinished['extract_id']);
		} catch (Exception $e) {
			print_r("could not archive log");
		}

		shell_exec(Conf::$software_path."extract/process_recorder.php failed ".escapeshellarg($unfinished['extract_id']));
		continue;
	}
}

?>