#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/database.php");

$event = $_SERVER["argv"][1];

switch ($event) {
	default:
		die("Y U NO event");
		break;
	case "started":
		//Handled by transform.php
		break;
	case "finished":
		//Handled by transform.php
		break;
	case "failed":
		$process_id = $_SERVER["argv"][2];

		if (preg_match("/^[0-9]+$/", $process_id) < 1) {
			die("process_id is not valid");
		}

		runq("UPDATE transform_processes SET finished=TRUE, finish_date=now(), failed=TRUE WHERE process_id='".pg_escape_string($process_id)."';");

		break;
}

?>