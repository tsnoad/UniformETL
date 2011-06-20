#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/autoload.php");
require_once("/etc/uniformetl/database.php");

class Launcher {
	function start() {
		//is this script already running?
		if (trim(shell_exec("ps h -C transform_launcher.php o pid | wc -l")) > 1) {
			//let's not step on our own toes
			die("transform launcher is currently running");
		}

		//are there any extracts currently in progress?
		$already_extracting = runq("SELECT count(*) FROM extract_processes WHERE finished=FALSE;");
		if ($already_extracting[0]['count'] > 0) {
			//we have to wait until they've finished
			die("extract is currently running");
		}

		//are there any transforms currently in progress?
		$already_transforming = runq("SELECT count(*) FROM transform_processes WHERE finished=FALSE;");
		if ($already_transforming[0]['count'] > 0) {
			//we have to wait until they've finished
			die("transform is currently running");
		}

		//find successful extracts that haven't had a transform started
		//sort by extract id, so that the newest is on top
		$candidates = runq("SELECT e.extract_id FROM extract_processes e LEFT OUTER JOIN transform_processes t ON (t.extract_id=e.extract_id) WHERE e.finished=TRUE AND e.failed=FALSE AND t.extract_id IS NULL ORDER BY e.extract_id DESC;");

		//if we've found an extract without a transform
		if (!empty($candidates[0]) && !empty($candidates[0]['extract_id'])) {
			$transform = $candidates[0];

			//start a transform process for this extract
			shell_exec(Conf::$software_path."transform/transform.php ".escapeshellarg($transform['extract_id'])." > ".Conf::$software_path."logs/transformlog 2>".Conf::$software_path."logs/transformlog & echo $!");

			//helpful message
			print_r("transform for {$transform['extract_id']} started");

			//all done
			return;
		}
	}
}

$launcher = New Launcher;
$launcher->start();

?>