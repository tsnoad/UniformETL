<?php

require_once("/etc/uniformetl/config.php");
$conf = New Conf();

function runq($query) {
	global $conf;

	$conn = pg_connect("host=".$conf->dbhost." port=5432 dbname=".$conf->dbname." user=".$conf->dbuser." password=".$conf->dbpass."");
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