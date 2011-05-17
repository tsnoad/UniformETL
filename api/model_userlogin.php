<?php

// For when someone wants to use the API to authenticate a member.
Class UserLogin {
	function who_me() {
		return preg_match("/^users\/[0-9]+\/login\/?$/", $_GET['url']);
	}

	function what_do_i_do() {
		//what's the request method?
		switch ($_SERVER['REQUEST_METHOD']) {
			case "GET":
				header("HTTP/1.1 400 Bad Request");
				die("HTTP/1.1 400 Bad Request");
				break;

			case "POST":
				$this->login();
				break;

			case "PUT":
			case "DELETE":
			default:
				header("HTTP/1.1 400 Bad Request");
				die("HTTP/1.1 400 Bad Request");
				break;
		}
	}

	function login() {
		//get the member id
		$member_id = $this->get_member_id();

		//get password from request
		$password = $_POST['password'];

		//no password?
		if (empty($password)) {
			header("HTTP/1.1 400 Bad Request");
			die("HTTP/1.1 400 Bad Request");
		}

		//get the member's password salt and hash
		$login_query = runq("SELECT m.member_id, p.salt, p.hash FROM member_ids m INNER JOIN passwords p ON (p.member_id=m.member_id) WHERE m.member_id='".pg_escape_string($member_id)."' LIMIT 1;");
		$login_tmp = $login_query[0];
	
		//try to log in
		if (empty($login_tmp) || $login_tmp['hash'] !== md5($login_tmp['salt'].$password)) {
			header("HTTP/1.1 401 Unauthorized");
			die("HTTP/1.1 401 Unauthorized");
	
		//login successful
		} else {
			header("HTTP/1.1 200 OK");
			print_r("HTTP/1.1 200 OK");
		}
	}

	function get_member_id() {
		//get the member id
		preg_match("/^users\/([0-9]+)\/login\/?$/", $_GET['url'], &$matches);
		$member_id = $matches[1];
	
		//no member id?
		if (empty($member_id)) {
			header("HTTP/1.1 400 Bad Request");
			die("HTTP/1.1 400 Bad Request");
		}

		return $member_id;
	}
}

?>