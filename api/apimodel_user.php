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
				$this->update_user();
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
		$user_query = runq("SELECT ".$get_user_member_ids[0].", ".implode(", ", $get_user_plugins_selects)." FROM ".$get_user_member_ids[1]." ".implode(" ", $get_user_plugins_froms)." WHERE m.member_id='".pg_escape_string($member_id)."' LIMIT 1;");
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

	function update_user() {
		//get the member id
		$member_id = $this->get_member_id();

		//get the transform models class, so we can access the models
		$models = New Models;

		//if we have to update the member's password
		if (isset($_POST['password'])) {
			//load the passwords model
			$password_model = $models->init_class("passwords");

			//data to pass on to model
			$add_or_update_data = array(
				"member_id" => $member_id,
				"password" => $_POST['password']
			);

			//update password, or create one if necessary
			$password_model->update_or_add_data($add_or_update_data);
		}

		//all good
		header("HTTP/1.1 200 OK");
		print_r("HTTP/1.1 200 OK");
	}

	function delete_user() {
		header("HTTP/1.1 501 Not Implemented");
		die("HTTP/1.1 501 Not Implemented");
	}

	function get_member_id() {
		//get the member id
		preg_match("/^users\/([0-9]+)\/?$/", $_GET['url'], &$matches);
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