#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/autoload.php");

$event = $_SERVER["argv"][1];

switch ($event) {
	default:
		die("Y U NO event");
		break;
	case "started":
		$extract_id = $_SERVER["argv"][2];
		$source_path = $_SERVER["argv"][3];
		$source_timestamp = $_SERVER["argv"][4];
		$source_md5 = $_SERVER["argv"][5];
		$extract_pid = $_SERVER["argv"][6];

		if (preg_match("/^[0-9]+$/", $extract_id) < 1) {
			die("extract_id is not valid");
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

		try {
			runq("INSERT INTO extract_processes (extract_id, extractor, extract_pid) VALUES ('".db_escape($extract_id)."', 'full', '".db_escape($extract_pid)."');");
	
			runq("INSERT INTO extract_full (extract_id, source_path, source_timestamp, source_md5) VALUES ('".db_escape($extract_id)."', '".db_escape($source_path)."', '".db_escape($source_timestamp)."', '".db_escape($source_md5)."');");

		} catch (Exception $e) {
			die("could not create process in database");
		}

		break;
	case "finished":
		$extract_id = $_SERVER["argv"][2];

		if (preg_match("/^[0-9]+$/", $extract_id) < 1) {
			die("extract_id is not valid");
		}

		try {
			runq("UPDATE extract_processes SET finished=TRUE, finish_date=now() WHERE extract_id='".db_escape($extract_id)."';");
		} catch (Exception $e) {
			die("could not update process in database");
		}

		break;
	case "failed":
		$extract_id = $_SERVER["argv"][2];

		if (preg_match("/^[0-9]+$/", $extract_id) < 1) {
			die("extract_id is not valid");
		}

		try {
			runq("UPDATE extract_processes SET finished=TRUE, finish_date=now(), failed=TRUE WHERE extract_id='".db_escape($extract_id)."';");
		} catch (Exception $e) {
			die("could not update process in database");
		}

		break;
}

?>