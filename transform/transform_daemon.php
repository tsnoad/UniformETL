#!/usr/bin/php5
<?php

/**
 * Transform Daemon
 *
 * Makes everything happen. Run every minute by sudo's crontab. Cleans up old
 * transform processes and starts new ones.
 *
 */

//get config settings
require_once("/etc/uniformetl/config.php");
$conf = New Conf;

//helpful log message
echo "\n";
echo str_pad(" Transform Daemon ", 80, "-", STR_PAD_BOTH)."\n";
echo str_pad(" ".date("r")." ", 80, " ", STR_PAD_BOTH)."\n";

//helpful log message
echo "Checking environment...\t";

//check that we're not already running
if (trim(shell_exec("ps h -C transform_daemon.php o pid | wc -l")) > 1) {
	//so we don't step on our own toes
	die("FAILED: daemon is already running");
}

//helpful log message
echo "OK\n";

//start the watcher to clean up any old transform processes
shell_exec($conf->software_path."transform/transform_watcher.php >> ".$conf->software_path."logs/transformdaemonlog");

//start the launcher to check if there's any completed extract processes that we can start transforming
shell_exec($conf->software_path."transform/transform_launcher.php >> ".$conf->software_path."logs/transformdaemonlog");

//helpful log message
echo str_pad(" Transform Daemon Complete ", 80, "-", STR_PAD_BOTH)."\n";

?>