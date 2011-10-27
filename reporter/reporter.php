<?php

require_once("/etc/uniformetl/autoload.php");
require_once("/etc/uniformetl/database.php");

/* php -r 'include("autoload.php"); $rep = new Reporter; $rep->start();' */

class Reporter {
	function start() {
		try {
			$failures = runq("select e.extract_id, e.start_date as extract_start_date, e.finish_date as extract_finish_date, e.failed as extract_failed, er.reported as extract_reported, t.transform_id, t.start_date as transform_start_date, t.finish_date as transform_finish_date, t.failed as transform_failed, tr.reported as transform_reported from extract_processes e left outer join transform_processes t on (t.extract_id=e.extract_id) left outer join extract_reports er on (er.extract_id=e.extract_id) left outer join transform_reports tr on (tr.transform_id=t.transform_id) where e.failed=TRUE or t.failed=TRUE;");
		} catch (Exception $e) {
			print_r($e->getMessage());
			die("could not get failed extracts");
		}

		if (empty($failures)) {
			//all is well
			return;
		}

		foreach ($failures as $failure) {
			if ($failure['extract_failed'] == '1' || $failure['extract_failed'] == 't') {
				if ($failure['extract_reported'] == '1' || $failure['extract_reported'] == 't') {
					//failure message has already been sent
					continue;
				}

				echo "Extract has failed";
				print_r($failure);

				print_r(file_get_contents(Conf::$software_path."logs/archive/extract".$failure['extract_id']));

				try {
					runq("INSERT INTO extract_reports (extract_id, reported) VALUES ('".db_escape($failure['extract_id'])."', TRUE);");
				} catch (Exception $e) {
					print_r("could not save report");
					print_r($e->getMessage());
				}

			} else if ($failure['transform_failed'] == '1' || $failure['transform_failed'] == 't') {
				if ($failure['transform_reported'] == '1' || $failure['transform_reported'] == 't') {
					//failure message has already been sent
					continue;
				}

				echo "Transform has failed";
				print_r($failure);

				print_r(file_get_contents(Conf::$software_path."logs/archive/transform".$failure['transform_id']));

				try {
					runq("INSERT INTO transform_reports (transform_id, reported) VALUES ('".db_escape($failure['transform_id'])."', TRUE);");
				} catch (Exception $e) {
					print_r("could not save report");
					print_r($e->getMessage());
				}
			}
		}
	}
}

?>