#!/usr/bin/php5
<?php

/**
 * Extract Daemon
 *
 * Makes everything happen. Run every minute by sudo's crontab. Cleans up old
 * extract processes and starts new ones.
 *
 */

//get config settings
require_once("/etc/uniformetl/config.php");
$conf = New Conf;

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
shell_exec($conf->software_path."extract/process_watcher.php >> ".$conf->software_path."logs/extractdaemonlog");

//start the launcher to check if there's any new data for us to process
/* shell_exec($conf->software_path."extract/extractors/full/extract_launcher.php >> ".$conf->software_path."logs/extractdaemonlog"); */

//helpful log message
echo str_pad(" Extract Daemon Complete ", 80, "-", STR_PAD_BOTH)."\n";

?>