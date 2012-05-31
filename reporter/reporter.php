<?php

require_once("/etc/uniformetl/autoload.php");
require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");

/* php -r 'include("autoload.php"); $rep = new Reporter; $rep->start();' */
/* php -r 'include("autoload.php"); $rep = new Reporter; $rep->report_history();' */

class Reporter {
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
		$hours = 72;

		try {
			$recents = runq("select e.extract_id, e.start_date as extract_start_date, e.finished as extract_finished, e.finish_date as extract_finish_date, e.failed as extract_failed, e.extractor, t.transform_id, t.start_date as transform_start_date, t.finished as transform_finished, t.finish_date as transform_finish_date, t.failed as transform_failed from extract_processes e left outer join transform_processes t on (t.extract_id=e.extract_id) where e.start_date>now()+interval '-".db_escape($hours)."hours' or e.finished=false or t.start_date>now()+interval '-".db_escape($hours)."hours' or t.finished=false order by e.start_date, t.start_date;");
		} catch (Exception $e) {
			print_r($e->getMessage());
			die("could not get recent extracts/transforms");
		}

		if (empty($recents)) {
			//all is well
			echo "no recent extracts/transforms\n";
			$body .= "no recent extracts/transforms\n";
			return;
		}

		foreach ($recents as $recent) {
/* 			print_r($recent); */

			echo date("Y-m-d H:i:s", strtotime($recent['extract_start_date']));
			$body .= date("Y-m-d H:i:s", strtotime($recent['extract_start_date']));

			if ($recent['extract_finished'] == '0' || $recent['extract_finished'] == 'f') {
				echo "extract in progress";
				$body .= "extract in progress";

			} else if (($recent['extract_finished'] == '1' || $recent['extract_finished'] == 't') && ($recent['extract_failed'] == '1' || $recent['extract_failed'] == 't')) {
				echo "extract failed";
				$body .= "extract failed";

				if (is_file(Conf::$software_path."logs/archive/extract".$recent['extract_id'])) {
					$log = file_get_contents(Conf::$software_path."logs/archive/extract".$recent['extract_id']);

					$fail_logs .= "Extract log for {$recent['extract_start_date']}\n--------\n\n{$log}\n\n";
				} else {
					echo "log not available";
					$body .= "log not available";
				}

			} else if (empty($recent['transform_id'])) {
				echo "no transform";
				$body .= "no transform";

			} else if ($recent['transform_finished'] == '0' || $recent['transform_finished'] == 'f') {
				echo "transform in process";
				$body .= "transform in process";

			} else if (($recent['transform_finished'] == '1' || $recent['transform_finished'] == 't') && ($recent['transform_failed'] == '1' || $recent['transform_failed'] == 't')) {
				echo "transform failed";
				$body .= "transform failed";

				if (is_file(Conf::$software_path."logs/archive/transform".$recent['transform_id'])) {
					$log = file_get_contents(Conf::$software_path."logs/archive/transform".$recent['transform_id']);

					$fail_logs .= "Transform log for {$recent['transform_start_date']}\n--------\n\n{$log}\n\n";
				} else {
					echo "log not available";
					$body .= "log not available";
				}

			} else {
				echo "finished";
				$body .= "finished";

				echo strtotime($recent['extract_finish_date']) - strtotime($recent['extract_start_date'])."s";
				$body .= strtotime($recent['extract_finish_date']) - strtotime($recent['extract_start_date'])."s";

				echo strtotime($recent['transform_finish_date']) - strtotime($recent['transform_start_date'])."s";
				$body .= strtotime($recent['transform_finish_date']) - strtotime($recent['transform_start_date'])."s";
			}

			echo "\n";
			$body .= "\n";
		}

		if (!empty($fail_logs)) {
			$body .= "\nFailure logs\n========\n\n{$fail_logs}";
		}

		$body = escapeshellarg($body);
		$subject = escapeshellarg("uETL {$hours} hours report");
		$recipients = escapeshellarg(Conf::$report_email_recipients);

		$mail_cmd = Conf::$report_email_cmd;
		$mail_cmd = str_replace("%{body}", $body, $mail_cmd);
		$mail_cmd = str_replace("%{subject}", $subject, $mail_cmd);
		$mail_cmd = str_replace("%{recipients}", $recipients, $mail_cmd);

		try {
			$mail_return = shell_exec($mail_cmd);
			if (empty($mail_return)) {
				throw new Exception('mail failed');
			}
		} catch (Exception $e) {
			print_r($e->getMessage());
			die("could not email report");
		}

		echo "history report sent.\n";
	}
}

?>