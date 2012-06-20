<?php

require_once("/etc/uniformetl/autoload.php");

Class APIModelPasswords {
	function who_me() {
		return preg_match("/^passwords\/?$/", $_GET['url']);
	}

	function what_do_i_do() {
		//what's the request method?
		switch ($_SERVER['REQUEST_METHOD']) {
			case "GET":
				$this->get_passwords();
				break;

			default:
				header("HTTP/1.1 405 Method Not Allowed");
				die("HTTP/1.1 405 Method Not Allowed");
				break;
		}
	}

	function get_passwords() {
		//make sure that the request is from a valid remote host
		if (empty($_SERVER['REMOTE_ADDR']) || !in_array($_SERVER['REMOTE_ADDR'], (array)Conf::$api_pass_rem_hosts)) {
			header("HTTP/1.1 401 Unauthorized");
			die("HTTP/1.1 401 Unauthorized");
		}

		//make sure the string of member ids is ok
		if (empty($_GET['member_ids']) || !preg_match("/^[0-9]+(\,[0-9]+)*$/", $_GET['member_ids'])) {
			header("HTTP/1.1 400 Bad Request");
			die("HTTP/1.1 400 Bad Request");
		}

		//create array of member ids
		$member_ids = explode(",", $_GET['member_ids']);

		//don't allow more than 250 at a time
		if (count($member_ids) > 250) {
			header("HTTP/1.1 400 Bad Request");
			die("HTTP/1.1 400 Bad Request");
		}

		//escape each member id
		$member_ids_esc = array_map("db_escape", $member_ids);

		//get data
		$passwords_query = runq("SELECT * FROM passwords WHERE member_id IN ('".implode("', '", $member_ids_esc)."');");

		$passwords = array();

		//create an array of member ids and passwords
		foreach ($passwords_query as $password) {
			$passwords[$password['member_id']] = $password['ldap_hash'];
		}
	
		//all good
		header("HTTP/1.1 200 OK");
	
		//return JSON string
		echo json_encode($passwords);
	}
}

?>