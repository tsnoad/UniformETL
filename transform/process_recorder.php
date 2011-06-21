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
		$transform_id = $_SERVER["argv"][2];

		if (preg_match("/^[0-9]+$/", $transform_id) < 1) {
			die("transform_id is not valid");
		}

		try {
			runq("UPDATE transform_processes SET finished=TRUE, finish_date=now(), failed=TRUE WHERE transform_id='".pg_escape_string($transform_id)."';");
		} catch (Exception $e) {
			die("could not update process in database");
		}

		break;
}

?>