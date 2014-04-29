<?php

require_once("/etc/uniformetl/autoload.php");

Class APIModelUser {
	function who_me() {
		return preg_match("/^users\/[0-9]+\/?$/", $_GET['url']);
	}

	function what_do_i_do() {
		//what's the request method?
		switch ($_SERVER['REQUEST_METHOD']) {
			case "GET":
				$this->get_user();
				break;

			case "POST":
				header("HTTP/1.1 405 Method Not Allowed");
				die("HTTP/1.1 405 Method Not Allowed");
				break;

			case "PUT":
				header("HTTP/1.1 405 Method Not Allowed");
				die("HTTP/1.1 405 Method Not Allowed");
				break;

			case "DELETE":
				$this->delete_user();
				break;

			default:
				header("HTTP/1.1 405 Method Not Allowed");
				die("HTTP/1.1 405 Method Not Allowed");
				break;
		}
	}

	function get_user() {
		//get the member id
		$member_id = $this->get_member_id();

		//we rely on the models to know what kind of data to present.
		//Single information: each model with will give us some SQL that we'll put together
		//to make a single query.
		$get_user_plugins = Plugins::hook("api_get-users_singles", array());

		//if we don't get a response from the member_ids model then somethings gone really wrong
		if (empty($get_user_plugins['MemberIds'])) {
			header("HTTP/1.1 500 Internal Server Error");
			die("HTTP/1.1 500 Internal Server Error");
		}

		//we treat the member_ids model differently - remove it from the array
		$get_user_member_ids = $get_user_plugins['MemberIds'];
		unset($get_user_plugins['MemberIds']);

		//seperate the SELECT and FROM sql from each model
		foreach ($get_user_plugins as $get_user_plugin) {
			$get_user_plugins_selects[] = $get_user_plugin[0];
			$get_user_plugins_froms[] = $get_user_plugin[1];
		}

		//put together the query and run it
		$user_query = runq("SELECT ".$get_user_member_ids[0].", ".implode(", ", $get_user_plugins_selects)." FROM ".$get_user_member_ids[1]." ".implode(" ", $get_user_plugins_froms)." WHERE m.member_id='".db_escape($member_id)."' LIMIT 1;");
		$user = $user_query[0];
	
		//not in database?
		if (empty($user_query)) {
			header("HTTP/1.1 404 Not Found");
			die("HTTP/1.1 404 Not Found");
		}

		//Plural information: each model will run it's own query and organise the data into a subarray
		$get_user_plurals_plugins = Plugins::hook("api_get-users_plurals", array($member_id));

		//add the subarrays into one array
		foreach ($get_user_plurals_plugins as $get_user_plurals_plugin) {
			$user = array_merge((array)$user, (array)$get_user_plurals_plugin);
		}
	
		//all good
		header("HTTP/1.1 200 OK");
	
		//return JSON string
		echo json_encode($user);
	}

/*
	function update_user() {
		//get the member id
		$member_id = $this->get_member_id();

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

			//primary callback -- will return 500 if callback fails
			if (isset(Conf::$api_passwd_callback_primary) && !empty(Conf::$api_passwd_callback_primary)) {
				$chp = curl_init();
				curl_setopt($chp, CURLOPT_URL, "http://".Conf::$api_passwd_callback_primary."/retl_request/interim/person/".$member_id);
				curl_setopt($chp, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($chp, CURLOPT_POST, true);
				curl_setopt($chp, CURLOPT_POSTFIELDS, null);
				curl_exec($chp);
				$response = curl_getinfo($chp, CURLINFO_HTTP_CODE);
				curl_close($chp);

				if ("200" != $response) {
					header("HTTP/1.1 500 Internal Server Error");
					die("HTTP/1.1 500 Internal Server Error");
				}
			}

			//secondary callback
			if (isset(Conf::$api_passwd_callback_secondary) && !empty(Conf::$api_passwd_callback_secondary)) {
				$chs = curl_init();
				curl_setopt($chs, CURLOPT_URL, "http://".Conf::$api_passwd_callback_secondary."/retl_request/interim/person/".$member_id);
				curl_setopt($chs, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($chs, CURLOPT_POST, true);
				curl_setopt($chs, CURLOPT_POSTFIELDS, null);
				curl_exec($chs);
				curl_close($chs);
			}
		}

		//all good
		header("HTTP/1.1 200 OK");
		print_r("HTTP/1.1 200 OK");
	}
*/

	function delete_user() {
		header("HTTP/1.1 501 Not Implemented");
		die("HTTP/1.1 501 Not Implemented");
	}

	function get_member_id() {
		//get the member id
		preg_match("/^users\/([0-9]+)\/?$/", $_GET['url'], $matches);
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