<?php

require_once("/etc/uniformetl/autoload.php");

function runq($query) {
	$conn = pg_connect("host=".Conf::$dbhost." port=5432 dbname=".Conf::$dbname." user=".Conf::$dbuser." password=".Conf::$dbpass."");
	$result = pg_query($conn, $query);

	if ($result === false) {
		throw new Exception(pg_last_error($conn));
	}

	if (stripos(trim($query), "select") !== 0) {
		return true;
	}

	$return = pg_fetch_all($result);

	pg_close($conn);

	return $return;
}

?>