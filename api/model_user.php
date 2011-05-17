<?php

Class User {
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
				header("HTTP/1.1 400 Bad Request");
				die("HTTP/1.1 400 Bad Request");
				break;

			case "DELETE":
				$this->delete_user();
				break;

			default:
				header("HTTP/1.1 400 Bad Request");
				die("HTTP/1.1 400 Bad Request");
				break;
		}
	}

	function get_user() {
		//get the member id
		$member_id = $this->get_member_id();

		//get the member's information
		$user_query = runq("SELECT m.member_id, w.member_id=m.member_id as web_status, e.member_id=m.member_id as ecpd_status FROM member_ids m LEFT JOIN web_statuses w ON (w.member_id=m.member_id) LEFT JOIN ecpd_statuses e ON (e.member_id=m.member_id) WHERE m.member_id='".pg_escape_string($member_id)."' LIMIT 1;");
		$user = $user_query[0];
	
		//not in database?
		if (empty($user_query)) {
			header("HTTP/1.1 404 Not Found");
			die("HTTP/1.1 404 Not Found");
		}
	
		//get member's names
		$names_query = runq("SELECT type, given_names, family_name FROM names n WHERE n.member_id='".pg_escape_string($member_id)."';");
		foreach ($names_query as $names_query_tmp) {
			//organise names by name type (there's only ever one name per name type)
			$user['names'][$names_query_tmp['type']] = array("given_names" => $names_query_tmp['given_names'], "family_name" => $names_query_tmp['family_name']);
		}
	
		//get member's email address
		//ALL email addresses are equal. there is no preffered email address.
		$emails_query = runq("SELECT email FROM emails e WHERE e.member_id='".pg_escape_string($member_id)."';");
		foreach ($emails_query as $emails_query_tmp) {
			//put email addresses in array
			$user['emails'][] = $emails_query_tmp['email'];
		}
	
		//get member's addresses
		$address_query = runq("SELECT type, address, suburb, state, postcode, country FROM addresses a WHERE a.member_id='".pg_escape_string($member_id)."';");
		$user['addresses'] = $address_query;
	
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
		$conf = New Conf;
		$models->conf = $conf;

		//if we have to update the member's password
		if (!empty($_POST['password'])) {
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