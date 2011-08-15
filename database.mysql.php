<?php

function db_format($query) {
	$query = preg_replace("/([^\ \(\)\=]*)::BIGINT/i", "convert($1, signed)", $query);

	$query = str_replace("E'\n'", "'\n'", $query);

	return $query;
}

function runq($query) {
	$conn = mysql_connect(Conf::$dbhost, Conf::$dbuser, Conf::$dbpass);

	mysql_select_db(Conf::$dbname, $conn);

	$return = db_query($conn, $query);

	mysql_close($conn);

	return $return;
}

function db_query($conn, $query) {
	$query = db_format($query);

	$result = mysql_query($query, $conn);

	if ($result === false) {
		throw new Exception(mysql_error($conn)."\nFor query: ".$query);
	}

	if (stripos(trim($query), "select") !== 0 && stripos(trim($query), "show") !== 0) {
		return true;
	}

	while ($row = mysql_fetch_assoc($result)) {
	    $return[] = $row;
	}

	if (empty($return)) {
		return false;
	}

	return $return;
}

function db_escape($string) {
	return mysql_escape_string($string);
}

function db_nextval($table, $column) {
	$nextval_query = runq("SELECT coalesce(max({$column}), 0)+1 AS nextval FROM {$table};");

	return $nextval_query[0]['nextval'];
}

function db_concat($argument1orarguments, $argument2=null, $argument3=null) {
	if (is_array($argument1orarguments)) {
		$arguments = $argument1orarguments;

	} else if (is_string($argument1orarguments)) {
		$arguments = array_merge((array)$argument1orarguments, (array)$argument2, (array)$argument3);
	}

	return "CONCAT(".implode(", ", $arguments).")";
}

function db_cast_bigint($column) {
	return $column;
}

function db_boolean($input) {
	if (is_bool($input)) {
		if ($input) {
			return "1";
		}
	} else if (is_integer($input)) {
		if ($input === 1) {
			return "1";
		}
	} else if (is_string($input)) {
		$input = trim(strtolower($input));
		if ($input == "1" || $input == "t" || $input == "true") {
			return "1";
		}
	}

	return "0";
}

?>