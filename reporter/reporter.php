<?php

require_once("/etc/uniformetl/autoload.php");
require_once("/etc/uniformetl/database.php");

/* php -r 'include("autoload.php"); $rep = new Reporter; $rep->start();' */
/* php -r 'include("autoload.php"); $rep = new Reporter; $rep->report_history();' */

class Reporter {
	function start() {
		$this->report_failures();
		$this->report_history();
	}

	function report_failures() {
		try {
			$failures = runq("select e.extract_id, e.start_date as extract_start_date, e.finish_date as extract_finish_date, e.failed as extract_failed, er.reported as extract_reported, t.transform_id, t.start_date as transform_start_date, t.finish_date as transform_finish_date, t.failed as transform_failed, tr.reported as transform_reported from extract_processes e left outer join transform_processes t on (t.extract_id=e.extract_id) left outer join extract_reports er on (er.extract_id=e.extract_id) left outer join transform_reports tr on (tr.transform_id=t.transform_id) where e.failed=TRUE or t.failed=TRUE;");
		} catch (Exception $e) {
			print_r($e->getMessage());
			die("could not get failed extracts/transforms");
		}

		if (empty($failures)) {
			//all is well
			echo "nothing to report\n";
			return;
		}

		$already_reported_count = 0;

		foreach ($failures as $failure) {
			if ($failure['extract_failed'] == '1' || $failure['extract_failed'] == 't') {
				if ($failure['extract_reported'] == '1' || $failure['extract_reported'] == 't') {
					//failure message has already been sent
					$already_reported_count ++;
					continue;
				}

				$body .= "Extract has failed\n--------\n\n";
				echo "Extract has failed:\n";

				if (is_file(Conf::$software_path."logs/archive/extract".$failure['extract_id'])) {
					$log = file_get_contents(Conf::$software_path."logs/archive/extract".$failure['extract_id']);
				} else {
					$log = "log not available";
					echo "log not available\n";
				}

			} else if ($failure['transform_failed'] == '1' || $failure['transform_failed'] == 't') {
				if ($failure['transform_reported'] == '1' || $failure['transform_reported'] == 't') {
					//failure message has already been sent
					$already_reported_count ++;
					continue;
				}

				$body .= "Transform has failed\n--------\n\n";
				echo "Transform has failed:\n";

				if (is_file(Conf::$software_path."logs/archive/transform".$failure['transform_id'])) {
					$log = file_get_contents(Conf::$software_path."logs/archive/transform".$failure['transform_id']);
				} else {
					$log = "log not available";
					echo "log not available\n";
				}
			}

			print_r($failure);

			$body .= "Process Information:\n";

			$body .= print_r($failure, true);

			$body .= "\n--------\n\n";
			$body .= "Log Information:\n";

			$body .= $log;

			$body = escapeshellarg($body);
			$subject = escapeshellarg("uETL failure notice");
			$recipients = escapeshellarg(Conf::$report_email_recipients);

			$mail_cmd = Conf::$report_email_cmd;
			$mail_cmd = str_replace("%{body}", $body, $mail_cmd);
			$mail_cmd = str_replace("%{subject}", $subject, $mail_cmd);
			$mail_cmd = str_replace("%{recipients}", $recipients, $mail_cmd);

 			var_dump(shell_exec($mail_cmd));

			echo "failure report sent.\n";

			if ($failure['extract_failed'] == '1' || $failure['extract_failed'] == 't') {
				try {
					runq("INSERT INTO extract_reports (extract_id, reported) VALUES ('".db_escape($failure['extract_id'])."', TRUE);");
				} catch (Exception $e) {
					print_r("could not save report");
					print_r($e->getMessage());
				}
			} else if ($failure['transform_failed'] == '1' || $failure['transform_failed'] == 't') {
				try {
					runq("INSERT INTO transform_reports (transform_id, reported) VALUES ('".db_escape($failure['transform_id'])."', TRUE);");
				} catch (Exception $e) {
					print_r("could not save report");
					print_r($e->getMessage());
				}
			}
		}

		echo "{$already_reported_count} failures previously reported\n";
	}

	function report_history() {
		try {
			$recents = runq("select e.extract_id, e.start_date as extract_start_date, e.finish_date as extract_finish_date, e.failed as extract_failed, t.transform_id, t.start_date as transform_start_date, t.finish_date as transform_finish_date, t.failed as transform_failed from extract_processes e left outer join transform_processes t on (t.extract_id=e.extract_id) where e.start_date>now()+interval '-24hours' or e.finished=false or t.start_date>now()+interval '-24hours' or t.finished=false;");
		} catch (Exception $e) {
			print_r($e->getMessage());
			die("could not get recent extracts/transforms");
		}

		print_r($recents);

		foreach ($recents as $recent) {
		}
	}
}

?>