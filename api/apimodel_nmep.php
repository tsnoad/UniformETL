<?php

require_once("/etc/uniformetl/autoload.php");

Class APIModelNmep {
	function who_me() {
		return preg_match("/^nmep\/[0-9]+\/?$/", $_GET['url']);
	}

	function what_do_i_do() {
		//what's the request method?
		switch ($_SERVER['REQUEST_METHOD']) {
			case "GET":
				$this->get_user();
				break;

			case "POST":
				$this->update_user();
				break;

			case "PUT":
			case "DELETE":
			default:
				header("HTTP/1.1 405 Method Not Allowed");
				die("HTTP/1.1 405 Method Not Allowed");
				break;
		}
	}

	function get_user() {
		//get the member id
		$member_id = $this->get_member_id();

		//put together the query and run it
		$user_query = runq("SELECT n.*, p.hash FROM nmep_statuses n LEFT OUTER JOIN passwords p ON (p.member_id=n.member_id) WHERE n.member_id='".db_escape($member_id)."' AND n.participant='t' LIMIT 1;");
		$user = $user_query[0];
	
		//not in database?
		if (empty($user_query)) {
			header("HTTP/1.1 404 Not Found");
			die("HTTP/1.1 404 Not Found");
		}

		//if the person already has a password set
		if (!empty($user["hash"])) {
			header("HTTP/1.1 403 Unauthorized");
			die("HTTP/1.1 403 Unauthorized");
		}
	
		//all good
		header("HTTP/1.1 200 OK");
	}

	function update_user() {
		//get the member id
		$member_id = $this->get_member_id();

		//put together the query and run it
		$user_query = runq("SELECT n.*, p.hash FROM nmep_statuses n LEFT OUTER JOIN passwords p ON (p.member_id=n.member_id) WHERE n.member_id='".db_escape($member_id)."' AND n.participant='t' LIMIT 1;");
		$user = $user_query[0];
	
		//not in database?
		if (empty($user_query)) {
			header("HTTP/1.1 404 Not Found");
			die("HTTP/1.1 404 Not Found");
		}

		//if the person already has a password set
		if (!empty($user["hash"])) {
			header("HTTP/1.1 403 Unauthorized");
			die("HTTP/1.1 403 Unauthorized");
		}


		//get the transform models class, so we can access the models
		$models = New Models;

		//if we have to update the member's password
		if (isset($_POST['password'])) {
			//load the passwords model
			$password_model = $models->init_class("MemberPasswords");

			//data to pass on to model
			$add_or_update_data = array(
				"member_id" => $member_id,
				"password" => $_POST['password']
			);

			//update password, or create one if necessary
			$password_model->update_or_add_data($add_or_update_data);

			if (in_array("MemberConfluenceStatuses", Conf::$do_transforms)) {
				//load the confluence statuses model
				$confluence_model = $models->init_class("MemberConfluenceStatuses");
				
				//get existing name, address, etc, and the new password from the database
				//then create a new entry in ldap, or update the existing one
				$confluence_model->update_password($member_id);
			}
		}

		//all good
		header("HTTP/1.1 200 OK");
		print_r("HTTP/1.1 200 OK");
	}

	function get_member_id() {
		//get the member id
		preg_match("/^nmep\/([0-9]+)\/?$/", $_GET['url'], $matches);
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