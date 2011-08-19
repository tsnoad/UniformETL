<?php

require_once("/etc/uniformetl/autoload.php");
require_once("/etc/uniformetl/database.php");

class TransformLauncher {
	function start() {
		//helpful message
		echo "Starting Launcher...\n";

		//helpful message
		echo "\tChecking environment...\t";

		//make sure nothing else is running
		$this->check_already_extracting();
		$this->check_already_transforming();

		//helpful log message
		echo "OK\n";

		//find successful extracts that haven't had a transform started
		//sort by extract id, so that the newest is on top
		$candidates = $this->get_candidate_extracts();

		//if we've found an extract without a transform
		if (!empty($candidates[0]) && !empty($candidates[0]['extract_id'])) {
			$transform = $candidates[0];

			//start a transform process for this extract
			shell_exec(Conf::$software_path."transform/transform.php ".escapeshellarg($transform['extract_id'])." > ".Conf::$software_path."logs/transformlog 2>".Conf::$software_path."logs/transformlog & echo $!");

			//helpful message
			print_r("transform for extract {$transform['extract_id']} started\n");

			//all done
			return;
		}
	}

	/*
	 * Check if there's an extract in progress
	 */
	function check_already_extracting() {
		//are there any extracts currently in progress?
		$already_extracting = runq("SELECT count(*) as count FROM extract_processes WHERE finished=FALSE;");
		if ($already_extracting[0]['count'] > 0) {
			//we have to wait until they've finished
			die("extract is currently running\n");
		}
	}

	/*
	 * Check if the laucher is already running, or if there's a transform in progress
	 */
	function check_already_transforming() {
		//is this script already running?
		if (trim(shell_exec("ps h -C run_transform_launcher.php o pid | wc -l")) > 1) {
			//let's not step on our own toes
			die("transform launcher is currently running\n");
		}

		//are there any transforms currently in progress?
		$already_transforming = runq("SELECT count(*) as count FROM transform_processes WHERE finished=FALSE;");
		if ($already_transforming[0]['count'] > 0) {
			//we have to wait until they've finished
			die("transform is currently running\n");
		}
	}

	function get_candidate_extracts() {
		//when did the latest transform finish?
		$latest_transform_query = runq("SELECT max(finish_date) as finish_date FROM transform_processes WHERE finished=TRUE");
		$latest_transform = $latest_transform_query[0]['finish_date'];

		//create some sql to filter out extracts that are too old
		if (!empty($latest_transform)) {
			$where_newer = "AND e.finish_date>'".db_escape($latest_transform)."'";
		} else {
			$where_newer = "";
		}

		//get extracts that:
		//have finished and have not failed
		//haven't had a transform started
		//and are more recent than the latest completed transform
		//sort the extracts by finish date so we can get the latest from the start of the array
		$candidates = runq("SELECT e.extract_id FROM extract_processes e LEFT OUTER JOIN transform_processes t ON (t.extract_id=e.extract_id) WHERE e.finished=TRUE AND e.failed=FALSE AND t.extract_id IS NULL {$where_newer} ORDER BY e.finish_date DESC;");

		return $candidates;
	}
}

?>