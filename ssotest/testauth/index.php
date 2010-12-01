<?php

require("lib.php");

$eassotestauth = New eassotestauth;

if (!empty($_POST['member_id']) && !empty($_POST['password'])) {
	$eassotestauth->login($_POST['member_id'], $_POST['password']);	
}

if (!empty($_COOKIE['session_key'])) {
	list($logged_in, $session_id, $session_key) = $eassotestauth->authenticate($_COOKIE['session_key']);	
}

if ($logged_in && $_GET['logout']) {
	$eassotestauth->logout($session_id, $session_key);
}

if ($logged_in) {
	echo "you are logged in<br />";
	echo "<a href='?logout=true'>logout</a>";
} else {
	echo "you are not logged in";
	?>
	<form method="post" action="#">
		<input type="text" name="member_id" />
		<input type="password" name="password" />
		<input type="submit" />
	</form>
	<?
}


?>
