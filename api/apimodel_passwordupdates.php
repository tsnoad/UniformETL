<?php

require_once("/etc/uniformetl/autoload.php");

Class APIModelPasswordUpdates {
	function who_me() {
		return preg_match("/^passwordupdates\/?$/", $_GET['url']);
	}

	function what_do_i_do() {
		//what's the request method?
		switch ($_SERVER['REQUEST_METHOD']) {
			case "GET":
				$this->get_changes();
				break;

			default:
				header("HTTP/1.1 405 Method Not Allowed");
				die("HTTP/1.1 405 Method Not Allowed");
				break;
		}
	}

	function get_changes() {
		//make sure that the request has used a valid api key
		if (empty($_SERVER['REMOTE_ADDR']) || !in_array($_SERVER['REMOTE_ADDR'], (array)Conf::$api_pass_rem_hosts)) {
			header("HTTP/1.1 401 Unauthorized");
			die("HTTP/1.1 401 Unauthorized");
		}

		//check that the dates are okay
		$ts_from = $this->check_date($_GET['from']);
		$ts_to = $this->check_date($_GET['to']);

		//from date must be before to date
		if ($ts_from >= $ts_to) {
			header("HTTP/1.1 400 Bad Request");
			die("HTTP/1.1 400 Bad Request");
		}

		$date_from = date("Y-m-d H:i:s", $ts_from);
		$date_to = date("Y-m-d H:i:s", $ts_to);

		//get the changes
		$changes_query = runq("SELECT * FROM password_changes WHERE change_date>='".db_escape($date_from)."' AND change_date<='".db_escape($date_to)."' LIMIT 10001;");

		//never get more than 10000
		if (count($changes_query) > 10000) {
			header("HTTP/1.1 503 Service Unavailable");
			die("HTTP/1.1 503 Service Unavailable");
		}

		$changed_users = array();

		//create an array of ids of members with changed passwords
		foreach ($changes_query as $change) {
			array_push($changed_users, $change['member_id']);
		}

		//remove duplicates
		$changed_users = array_unique($changed_users);

		//rekey will be required after duplicates are removed
		$changed_users = array_values($changed_users);
	
		//all good
		header("HTTP/1.1 200 OK");
	
		//return JSON string
		echo json_encode($changed_users);
	}

	function check_date($date_in) {
		if (empty($date_in) || !preg_match("/^[0-9][0-9][0-9][0-9]\-[0-9][0-9]\-[0-9][0-9]\ [0-9][0-9]\:[0-9][0-9]\:[0-9][0-9]$/", $date_in)) {
			header("HTTP/1.1 400 Bad Request");
			die("HTTP/1.1 400 Bad Request");
		}

		$ts_out = strtotime($date_in);

		if (empty($ts_out)) {
			header("HTTP/1.1 400 Bad Request");
			die("HTTP/1.1 400 Bad Request");
		}

		return $ts_out;
	}
}

?>