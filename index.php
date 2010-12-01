<?

function runq($query) {
	$conn = pg_connect("host=localhost dbname=hotel user=user password=skoobar");
	$result = pg_query($conn, $query);
	$return = pg_fetch_all($result);
	pg_close($conn);

	return $return;
}

class user {
	function __construct () {
		switch ($_GET['format']) {
			default:
			case "json":
				$this->format = "json";
				break;
			case "xml":
				$this->format = "xml";
				break;
		}
	}

	function init($request) {
		$this->request = $request;

		if (!empty($this->request[1])) {
			if (!is_numeric($this->request[1])) {
				header("HTTP/1.1 404");
				die("Not Found");
			}

			$this->userId = $this->request[1];

			$this->getUser();

			if (empty($this->request[2])) {
				$this->returnUser();
			} else if ($this->request[2] == "names") {
				$this->returnUserNames();
			} else if ($this->request[2] == "emails") {
				$this->returnUserEmails();
			} else if ($this->request[2] == "checkpassword") {
				$this->returnCheckpassword();
			} else {
				header("HTTP/1.1 404");
				die("Not Found");
			}
		}
	}

	function getUser() {
		$user_query = runq("SELECT m.member_id, w.member_id=m.member_id as web_status, e.member_id=m.member_id as ecpd_status FROM member_ids m LEFT JOIN web_statuses w ON (w.member_id=m.member_id) LEFT JOIN ecpd_statuses e ON (e.member_id=m.member_id) WHERE m.member_id='{$this->userId}';");

		if (empty($user_query)) {
			die("furk");
		}

		$this->user = $user_query[0];

		$names_query = runq("SELECT * FROM names n WHERE n.member_id='{$this->userId}';");
		$this->user['names'] = $names_query;

		$names_query = runq("SELECT * FROM emails e WHERE e.member_id='{$this->userId}';");
		$this->user['emails'] = $names_query;

		$address_query = runq("SELECT * FROM addresses a WHERE a.member_id='{$this->userId}';");
		$this->user['addresses'] = $address_query;
	}

	function returnUser() {
		$this->returnData($this->user);
	}

	function returnUserNames() {
		$this->returnData($this->user['names']);
	}

	function returnUserEmails() {
		$this->returnData($this->user['emails']);
	}

	function returnCheckpassword() {
		if (empty($_GET['password']) || $_GET['password'] != "squiggles") {
			header("HTTP/1.1 403");
			die("Forbidden");
		} else {
			$this->returnUser();
		}
	}

	function returnData($data) {

		if ($this->format == "json") {
			$this->returnJSONData($data);

		} else if ($this->format == "xml") {
			$this->returnXMLData($data);
		}
	}

	function returnJSONData($data) {
		header("HTTP/1.1 200 OK");
/* 		header("Content-type: application/json"); */
		echo json_encode($data);
	}

	function returnXMLData($data) {
		header("HTTP/1.1 200 OK");
/* 		header("Content-type: application/xml"); */
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
}

/* var_dump($_SERVER['REQUEST_METHOD']); */
/* print_r($_GET); */

$request = explode("/", $_GET['url']);

/* print_r($request); */

switch ($request[0]) {
	case "user":
		$user = New user();
		$user->init($request);
		break;
	default:
		header("HTTP/1.1 404");
		die("Not Found");
		break;
}

?>