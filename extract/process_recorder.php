#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/database.php");

$event = $_SERVER["argv"][1];

switch ($event) {
	default:
		die("Y U NO event");
		break;
	case "started":
		$process_id = $_SERVER["argv"][2];
		$source_path = $_SERVER["argv"][3];
		$source_timestamp = $_SERVER["argv"][4];
		$source_md5 = $_SERVER["argv"][5];
		$extract_pid = $_SERVER["argv"][6];

		if (preg_match("/^[0-9]+$/", $process_id) < 1) {
			die("process_id is not valid");
		}

		if (!is_string($source_path) && substr($source_path, -4, 4) != ".tgz") {
			die("source_path is not valid");
		}

		if (!is_string($source_timestamp)) {
			die("source_timestamp is not valid");
		}

		if (preg_match("/^[0-9a-zA-Z]{32,32}$/", $source_md5) < 1) {
			die("source_md5 is not valid");
		}

		if (preg_match("/^[0-9]+$/", $extract_pid) < 1) {
			die("extract_pid is not valid");
		}

		runq("INSERT INTO extract_processes (process_id, source_path, source_timestamp, source_md5, extract_pid) VALUES ('".pg_escape_string($process_id)."', '".pg_escape_string($source_path)."', '".pg_escape_string($source_timestamp)."', '".pg_escape_string($source_md5)."', '".pg_escape_string($extract_pid)."');");

		if (false) {
			die();
		}

		break;
	case "finished":
		$process_id = $_SERVER["argv"][2];

		if (preg_match("/^[0-9]+$/", $process_id) < 1) {
			die("process_id is not valid");
		}

		runq("UPDATE extract_processes SET finished=TRUE, finish_date=now() WHERE process_id='".pg_escape_string($process_id)."';");

		break;
	case "failed":
		$process_id = $_SERVER["argv"][2];

		if (preg_match("/^[0-9]+$/", $process_id) < 1) {
			die("process_id is not valid");
		}

		runq("UPDATE extract_processes SET finished=TRUE, finish_date=now(), failed=TRUE WHERE process_id='".pg_escape_string($process_id)."';");

		break;
}

?>