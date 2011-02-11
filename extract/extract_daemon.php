#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/config.php");

if (false) {
	die("extract daemon is already running");
}

while (true) {
	var_dump("running watcher");

	shell_exec("/home/user/hotel/extract/process_watcher.php >> /home/user/hotel/logs/extractdaemonlog");

	var_dump("running launcher");		

	shell_exec("/home/user/hotel/extract/extract_launcher.php >> /home/user/hotel/logs/extractdaemonlog");

	sleep(1);
}

?>