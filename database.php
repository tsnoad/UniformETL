<?php

require_once("/etc/uniformetl/autoload.php");

function runq($query) {
	$conn = pg_connect("host=".Conf::$dbhost." port=5432 dbname=".Conf::$dbname." user=".Conf::$dbuser." password=".Conf::$dbpass."");
	$result = pg_query($conn, $query);

	if (stripos(trim($query), "insert") === 0) {
		return ($result != false);

	} else if ($result === false) {
		return false; 
	}

	$return = pg_fetch_all($result);
	pg_close($conn);

	return $return;
}

?>