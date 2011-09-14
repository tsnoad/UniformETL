#!/usr/bin/php5
<?php

/**
 * Extract Daemon
 *
 * Makes everything happen. Run every minute by sudo's crontab. Cleans up old
 * extract processes and starts new ones.
 *
 */

require_once("/etc/uniformetl/autoload.php");

//helpful log message
echo "\n";
echo str_pad(" Extract Daemon ", 80, "-", STR_PAD_BOTH)."\n";
echo str_pad(" ".date("r")." ", 80, " ", STR_PAD_BOTH)."\n";

//helpful log message
echo "Checking environment...\t";

//check that we're not already running
if (trim(shell_exec("ps h -C extract_daemon.php o pid | wc -l")) > 1) {
	//so we don't step on our own toes
	die("FAILED: daemon is already running");
}

//helpful log message
echo "OK\n";

//start the watcher to clean up any old extract processes
shell_exec(Conf::$software_path."extract/process_watcher.php >> ".Conf::$software_path."logs/extractdaemonlog");

//start the launcher to check if there's any new data for us to process
/*
shell_exec(Conf::$software_path."extract/extractors/full/run_extract_launcher.php >> ".Conf::$software_path."logs/extractdaemonlog");

shell_exec(Conf::$software_path."extract/extractors/latest/run_extract_launcher.php >> ".Conf::$software_path."logs/extractdaemonlog");
*/

foreach (Conf::$run_extractors as $run_extractor) {
	unset($launcher);

	switch ($run_extractor) {
		case "ExtractFullLauncher":
			$launcher = New ExtractFullLauncher;
			break;
		case "ExtractLatest":
			$launcher = New ExtractLatest;
			break;
		case "ExtractFullStagingLauncher":
			$launcher = New ExtractFullStagingLauncher;
			break;
		case "ExtractLatestStagingLauncher":
			$launcher = New ExtractLatestStagingLauncher;
			break;
		case "ExtractFullStagedLauncher":
			$launcher = New ExtractFullStagedLauncher;
			break;
		case "ExtractLatestStagedLauncher":
			$launcher = New ExtractLatestStagedLauncher;
			break;
		default:
			die("{$run_extractor} is not a valid extractor\n");
			break;
	}

	$launcher->start();

	//sleep for one second.
	sleep(1);
}

//helpful log message
echo str_pad(" Extract Daemon Complete ", 80, "-", STR_PAD_BOTH)."\n";

?>