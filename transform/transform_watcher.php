#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/autoload.php");
require_once("/etc/uniformetl/database.php");

//helpful log message
echo "Starting Watcher...\n";

//helpful log message
echo "\tChecking environment...\t";

if (trim(shell_exec("ps h -C transform_watcher.php o pid | wc -l")) > 1) {
	die("watcher is already running\n");
}

//helpful log message
echo "OK\n";

//helpful log message
echo "\tSearching for unfinished processes...";

$unfinisheds_query = runq("SELECT * FROM transform_processes WHERE finished=FALSE;");

if (empty($unfinisheds_query)) {
	die("\tno active transform processes\n");
}

echo "\t".count($unfinisheds_query)." process".(count($unfinisheds_query) === 1 ? "" : "es")." found\n";

foreach ($unfinisheds_query as $unfinished) {
	echo "\t\t"."transform #{$unfinished['transform_id']} ({$unfinished['transform_pid']}):";

	$unfinished_status = shell_exec("ps h p ".escapeshellarg($unfinished['transform_pid'])." o pid | wc -l");

	if (trim($unfinished_status) > 0) {
		//process is running, all is well
		echo "\t"."active\n";
		continue;
	}

	//process is no longer running, we need to wait to see if it's recorded as finished
	echo "\t"."stopped. wait 5s...\n";

	sleep(5);

	$status_query = runq("SELECT * FROM transform_processes WHERE transform_id='".db_escape($unfinished['transform_id'])."' LIMIT 1;");

	if ($status_query[0]['finished'] == 't') {
		//process has completed normally
		echo "\t"."completed.\n";
		continue;

	} else {
		echo "\t"."failed.\n";

		try {
			copy(Conf::$software_path."logs/transformlog", Conf::$software_path."logs/archive/transform".$unfinished['transform_id']);
		} catch (Exception $e) {
			print_r("could not archive log");
		}

		shell_exec(Conf::$software_path."transform/process_recorder.php failed ".escapeshellarg($unfinished['transform_id']));
		continue;
	}
}

?>