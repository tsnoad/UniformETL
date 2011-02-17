#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/config.php");

$conf = New Conf;

echo "########\n";
echo "########\n";

echo "starting transform daemon\n";
echo date("r")."\n";
echo "========\n";

echo "checking environment\n";
echo date("r")."\n";
echo "========\n";

if (trim(shell_exec("ps h -C transform_daemon.php o pid | wc -l")) > 1) {
	die("transform daemon is already running");
}

/* shell_exec("/home/user/hotel/transform/process_watcher.php >> /home/user/hotel/logs/extractdaemonlog"); */

shell_exec($conf->software_path."transform/transform_launcher.php >> ".$conf->software_path."logs/transformdaemonlog");

?>