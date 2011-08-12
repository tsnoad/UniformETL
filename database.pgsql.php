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

function db_concat($argument1orarguments, $argument2=null, $argument3=null) {
	if (is_array($argument1orarguments)) {
		$arguments = $argument1orarguments;

	} else if (is_string($argument1orarguments)) {
		$arguments = array_merge((array)$argument1orarguments, (array)$argument2, (array)$argument3);
	}

	return implode("||", $arguments);
}

function db_cast_bigint($column) {
	return $column."::BIGINT";
}

function db_boolean($input) {
	if (is_bool($input)) {
		if ($input) {
			return "t";
		}
	} else if (is_integer($input)) {
		if ($input === 1) {
			return "t";
		}
	} else if (is_string($input)) {
		$input = trim(strtolower($input));
		if ($input == "1" || $input == "t" || $input == "true") {
			return "t";
		}
	}

	return "f";
}

?>