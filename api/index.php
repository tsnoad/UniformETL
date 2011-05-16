<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models.php");

/*
function returnXMLData($data) {
	header("HTTP/1.1 200 OK");
	echo "<?xml version=\"1.0\"?>\n";
	echo "<user>\n";

	foreach ($data as $data_element_name => $data_element) {
		if (is_array($data_element)) {
			echo "\t<{$data_element_name}>\n";

			foreach ($data_element as $data_group) {
				echo "\t\t<".substr($data_element_name, 0, -1).">\n";

				foreach ($data_group as $data_group_element_name => $data_group_element) {
					echo "\t\t\t<{$data_group_element_name}>{$data_group_element}</{$data_group_element_name}>\n";
				}

				echo "\t\t</".substr($data_element_name, 0, -1).">\n";
			}

			echo "\t</{$data_element_name}>\n";
		} else {
			echo "\t<{$data_element_name}>{$data_element}</{$data_element_name}>\n";
		}
	}

	echo "</user>\n";
}
*/

Class Users {
}

Class User {
}

Class UserLogin {
}

//if the request is for /users
if (preg_match("/^users\/?$/", $_GET['url'])) {
	//what's the request method?
	switch ($_SERVER['REQUEST_METHOD']) {
		case "GET":
			header("HTTP/1.1 501 Not Implemented");
			die("HTTP/1.1 501 Not Implemented");
			break;
		case "POST":
		case "PUT":
		case "DELETE":
		default:
			header("HTTP/1.1 400 Bad Request");
			die("HTTP/1.1 400 Bad Request");
			break;
	}

} else if (preg_match("/^users\/[0-9]+\/?$/", $_GET['url'])) {
	//get the member id
	preg_match("/^users\/([0-9]+)\/?$/", $_GET['url'], &$matches);
	$member_id = $matches[1];

	//no member id?
	if (empty($member_id)) {
		header("HTTP/1.1 400 Bad Request");
		die("HTTP/1.1 400 Bad Request");
	}

	//what's the request method?
	switch ($_SERVER['REQUEST_METHOD']) {
		//GET: return information about the member
		case "GET":
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

			break;
		//POST: update member's information
		case "POST":
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

			break;
		case "PUT":
		case "DELETE":
			header("HTTP/1.1 501 Not Implemented");
			die("HTTP/1.1 501 Not Implemented");
			break;
		default:
			header("HTTP/1.1 400 Bad Request");
			die("HTTP/1.1 400 Bad Request");
			break;
	}

} else if (preg_match("/^users\/[0-9]+\/login\/?$/", $_GET['url'])) {
	//get the member id
	preg_match("/^users\/([0-9]+)\/login\/?$/", $_GET['url'], &$matches);
	$member_id = $matches[1];

	//no member id?
	if (empty($member_id)) {
		header("HTTP/1.1 400 Bad Request");
		die("HTTP/1.1 400 Bad Request");
	}

	//what's the request method?
	switch ($_SERVER['REQUEST_METHOD']) {
		case "GET":
			header("HTTP/1.1 400 Bad Request");
			die("HTTP/1.1 400 Bad Request");
			break;
		//POST: try to log in with submitted data
		case "POST":
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
			break;
		case "PUT":
		case "DELETE":
		default:
			header("HTTP/1.1 400 Bad Request");
			die("HTTP/1.1 400 Bad Request");
			break;
	}

} else {
	header("HTTP/1.1 400 Bad Request");
	die("HTTP/1.1 400 Bad Request");
}

?>