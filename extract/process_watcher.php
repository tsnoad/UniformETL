#!/usr/bin/php5
<?php

function runq($query) {
	$conn = pg_connect("dbname=hotel user=user");
	$result = pg_query($conn, $query);
	$return = pg_fetch_all($result);
	pg_close($conn);

	return $return;
}

$unfinisheds_query = runq("SELECT * FROM extract_processes WHERE finished=FALSE;");

if (empty($unfinisheds_query)) {
	die("no active extract processes");
}

echo "\n"."Found ".count($unfinisheds_query)." processes";

foreach ($unfinisheds_query as $unfinished) {
	echo "\n"."Checking process:";
	$unfinished_status = shell_exec("ps h p ".escapeshellarg($unfinished['extract_pid'])." o pid | wc -l");

	if (trim($unfinished_status) > 0) {
		//process is running, all is well
		echo "\t"."running";
		continue;
	}

	//process is no longer running, we need to wait to see if it's recorded as finished
	echo "\t"."stopped. wait 5s...";

	sleep(5);

	$status_query = runq("SELECT * FROM extract_processes WHERE process_id='".pg_escape_string($unfinished['process_id'])."' LIMIT 1;");

	if ($status_query[0]['finished'] == 't') {
		//process has completed normally
		echo "\t"."completed.";
		continue;

	} else {
		echo "\t"."failed.";
		shell_exec("/home/user/hotel/extract/process_recorder.php failed ".escapeshellarg($unfinished['process_id']));
		continue;
	}
}

echo "\n";

?>