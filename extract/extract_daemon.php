#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/config.php");

$conf = New Conf;

echo "########\n";
echo "########\n";

echo "starting extract daemon\n";
echo date("r")."\n";
echo "========\n";

echo "checking environment\n";
echo date("r")."\n";
echo "========\n";

if (trim(shell_exec("ps h -C extract_daemon.php o pid | wc -l")) > 1) {
	die("extract daemon is already running");
}

shell_exec($conf->software_path."extract/process_watcher.php >> ".$conf->software_path."logs/extractdaemonlog");

shell_exec($conf->software_path."extract/extract_launcher.php >> ".$conf->software_path."logs/extractdaemonlog");

?>