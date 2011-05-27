<?php

Class Users {
	function who_me() {
		return preg_match("/^users\/?$/", $_GET['url']);
	}

	function what_do_i_do() {
		//what's the request method?
		switch ($_SERVER['REQUEST_METHOD']) {
			case "GET":
				$this->get_users();
				break;

			case "POST":
				header("HTTP/1.1 405 Method Not Allowed");
				die("HTTP/1.1 405 Method Not Allowed");
				break;

			case "PUT":
				$this->create_user();
				break;

			case "DELETE":
			default:
				header("HTTP/1.1 405 Method Not Allowed");
				die("HTTP/1.1 405 Method Not Allowed");
				break;
		}
	}

	function get_users() {
		header("HTTP/1.1 501 Not Implemented");
		die("HTTP/1.1 501 Not Implemented");
	}

	function create_user() {
		header("HTTP/1.1 501 Not Implemented");
		die("HTTP/1.1 501 Not Implemented");
	}
}

?>