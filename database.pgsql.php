<?php

function runq($query) {
	$conn = pg_connect("host=".Conf::$dbhost." port=5432 dbname=".Conf::$dbname." user=".Conf::$dbuser." password=".Conf::$dbpass."");

	$return = db_query($conn, $query);

	pg_close($conn);

	return $return;
}

function db_query($conn, $query) {
	$result = pg_query($conn, $query);

	if ($result === false) {
		throw new Exception(pg_last_error($conn));
	}

	if (stripos(trim($query), "select") !== 0) {
		return true;
	}

	$return = pg_fetch_all($result);

	return $return;
}

function db_escape($string) {
	return pg_escape_string($string);
}

function db_nextval($table, $column) {
	$nextval_query = runq("SELECT nextval('{$table}_{$column}_seq');");

	return $nextval_query[0]['nextval'];
}

function db_concat($arguments) {
	return implode("||", $arguments);
}

function db_cast_bigint($column) {
	return $column."::BIGINT";
}

?>