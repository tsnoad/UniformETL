#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/autoload.php");
require_once("/etc/uniformetl/database.php");

class Launcher {
	function start() {
		if (trim(shell_exec("ps h -C transform_launcher.php o pid | wc -l")) > 1) {
			die("transform launcher is currently running");
		}

		$already_extracting = runq("SELECT count(*) FROM extract_processes WHERE finished=FALSE;");

		if ($already_extracting[0]['count'] > 0) {
			die("extract is currently running");
		}

		$already_transforming = runq("SELECT count(*) FROM transform_processes WHERE finished=FALSE;");

		if ($already_transforming[0]['count'] > 0) {
			die("transform is currently running");
		}

		$candidates = runq("SELECT p.process_id FROM processes p INNER JOIN extract_processes ep ON (ep.process_id=p.process_id) LEFT OUTER JOIN transform_processes tp ON (tp.process_id=p.process_id) WHERE ep.finished=TRUE AND ep.failed=FALSE AND tp.process_id IS NULL ORDER BY p.process_id DESC;");

		foreach ($candidates as $transform) {
			shell_exec(Conf::$software_path."transform/transform.php ".escapeshellarg($transform['process_id'])." > ".Conf::$software_path."logs/transformlog 2>".Conf::$software_path."logs/transformlog & echo $!");
			die("transform for {$transform['process_id']} started");
		}
	}
}

$launcher = New Launcher;
$launcher->start();

?>