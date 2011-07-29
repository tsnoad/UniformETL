<?php

require_once("/etc/uniformetl/autoload.php");

if (Conf::$dblang == "pgsql") {
	require("/etc/uniformetl/database.pgsql.php");

} else if (Conf::$dblang == "mysql") {
	require("/etc/uniformetl/database.mysql.php");

} else {
	throw new Exception("Don't know DB language: ".Conf::$dblang);
}


function db_choose($choice1orchoices, $choice2=null, $choice3=null) {
	$choices = array_merge((array)$choice1orchoices, (array)$choice2, (array)$choice3);

	if (empty($choices[Conf::$dblang])) {
/* 		throw new Exception("No SQL given for ".Conf::$dblang); */
		return "";
	}

	return $choices[Conf::$dblang];
};

function db_mysql($query) {
	return array("mysql" => $query);
}

function db_pgsql($query) {
	return array("pgsql" => $query);
}

?>