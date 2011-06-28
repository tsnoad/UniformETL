<?php

require_once("/etc/uniformetl/autoload.php");

// For when someone wants to know if we're a teapot
Class APIModelTeapot {
	//can this model process the request
	function who_me() {
		return preg_match("/^teapot\/?$/", $_GET['url']);
	}

	//Yep, we are a teapot...
	function what_do_i_do() {
		header("HTTP/1.1 418 I'm a teapot");
		die("HTTP/1.1 418 I'm a teapot");
	}
}

?>