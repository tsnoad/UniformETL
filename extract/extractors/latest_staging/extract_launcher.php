<?php

/**
 * Extract Process Launcher
 *
 * Looks for new data to process. This script is run every minute by the
 * extract daemon. It checks for new tar files on the target server, and when
 * it finds one it checks that it's not still being modified, that we havn't
 * already processed it, and that it's not older than the last file we
 * processed. Then, if everything's okay the file is processed.
 *
 */

require_once("/etc/uniformetl/autoload.php");
require_once("/etc/uniformetl/database.php");

class ExtractLatestStagingLauncher {
	function start() {
		//helpful log message
		echo "Starting Launcher...\n";
		echo "\tChecking environment...\t";

		//make sure nothing else is running
		$this->check_already_extracting();
		$this->check_already_transforming();

		$previous_extract = runq("SELECT * FROM extract_processes WHERE extractor='latest_staging' AND finished=TRUE ORDER BY finish_date DESC LIMIT 1;");

		if (!empty($previous_extract)) {
			$time_since = time() - strtotime($previous_extract[0]['finish_date']);
			
			if ($time_since < 3600) {
				die("latest extract was run {$time_since}s seconds ago. need to wait longer between latest extracts...\n");
			}
		}

		//helpful log message
		echo "OK\n";

		//start the extract on the file
		$this->start_extract($file);

		//helpful log message
		print_r("dump started");

		//we only want to start one extract
		return;
	}

	/*
	 * Check if the laucher is already running, or if there's an extract in progress
	 */
	function check_already_extracting() {
		//check if there are any extract processes running at the moment
		$already_extracting = runq("SELECT count(*) as count FROM extract_processes WHERE finished=FALSE;");

		//we'll have to wait until the extract finishes
		if ($already_extracting[0]['count'] > 0) {
			die("extract is currently running\n");
		}
	}

	/*
	 * Check if there's a transform in progress
	 */
	function check_already_transforming() {
		//check if there are any transform processes running at the moment
		$already_transforming = runq("SELECT count(*) as count FROM transform_processes WHERE finished=FALSE;");

		//we'll have to wait until the transform finishes
		if ($already_transforming[0]['count'] > 0) {
			die("transform is currently running\n");
		}
	}



	/*
	 * Start an extract process using a file we've found
	 */
	function start_extract($file) {
		//helpful log message
		var_dump("jackpot");

		//start the extract process
		shell_exec(Conf::$software_path."extract/extractors/latest_staging/run_extract.php > ".Conf::$software_path."logs/extractlog &");
	}
}

?>