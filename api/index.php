<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");

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

if (preg_match("/^users\/?$/", $_GET['url'])) {
	header("HTTP/1.1 501 Not Implemented");
	die("HTTP/1.1 501 Not Implemented");

} else if (preg_match("/^users\/[0-9]+\/?$/", $_GET['url'])) {
	preg_match("/^users\/([0-9]+)\/?$/", $_GET['url'], &$matches);
	$member_id = $matches[1];

	if (empty($member_id)) {
		header("HTTP/1.1 400 Bad Request");
		die("HTTP/1.1 400 Bad Request");
	}

	$user_query = runq("SELECT m.member_id, w.member_id=m.member_id as web_status, e.member_id=m.member_id as ecpd_status FROM member_ids m LEFT JOIN web_statuses w ON (w.member_id=m.member_id) LEFT JOIN ecpd_statuses e ON (e.member_id=m.member_id) WHERE m.member_id='".pg_escape_string($member_id)."' LIMIT 1;");
	$user = $user_query[0];

	if (empty($user_query)) {
		header("HTTP/1.1 404 Not Found");
		die("HTTP/1.1 404 Not Found");
	}

	$names_query = runq("SELECT type, given_names, family_name FROM names n WHERE n.member_id='".pg_escape_string($member_id)."';");
	foreach ($names_query as $names_query_tmp) {

		$user['names'][$names_query_tmp['type']] = array("given_names" => $names_query_tmp['given_names'], "family_name" => $names_query_tmp['family_name']);
	}

	$emails_query = runq("SELECT email FROM emails e WHERE e.member_id='".pg_escape_string($member_id)."';");
	foreach ($emails_query as $emails_query_tmp) {
		$user['emails'][] = $emails_query_tmp['email'];
	}

	$address_query = runq("SELECT type, address, suburb, state, postcode, country FROM addresses a WHERE a.member_id='".pg_escape_string($member_id)."';");
	$user['addresses'] = $address_query;

	header("HTTP/1.1 200 OK");

	echo json_encode($user);

} else if (preg_match("/^users\/[0-9]+\/login\/?$/", $_GET['url'])) {
	preg_match("/^users\/([0-9]+)\/login\/?$/", $_GET['url'], &$matches);
	$member_id = $matches[1];

	$password = $_POST['password'];

	if (empty($member_id) || empty($password)) {
		header("HTTP/1.1 400 Bad Request");
		die("HTTP/1.1 400 Bad Request");
	}

	$login_query = runq("SELECT m.member_id, p.salt, p.hash FROM member_ids m INNER JOIN passwords p ON (p.member_id=m.member_id) WHERE m.member_id='".pg_escape_string($member_id)."' LIMIT 1;");

	$login_tmp = $login_query[0];

	if (empty($login_tmp) || $login_tmp['hash'] !== md5($login_tmp['salt'].$password)) {
		header("HTTP/1.1 401 Unauthorized");
		die("HTTP/1.1 401 Unauthorized");

	} else {
		header("HTTP/1.1 200 OK");
		print_r("HTTP/1.1 200 OK");
	}

} else {
	header("HTTP/1.1 400 Bad Request");
	die("HTTP/1.1 400 Bad Request");
}

?>