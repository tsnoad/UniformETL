<?php

require("../testauth/lib.php");

$eassotestauth = New eassotestauth;

if (!empty($_COOKIE['session_key'])) {
	list($logged_in, $session_id, $session_key) = $eassotestauth->authenticate($_COOKIE['session_key']);	
}

if ($logged_in) {
	echo "you are logged in";
} else {
	echo "you are not logged in";

}

?>
