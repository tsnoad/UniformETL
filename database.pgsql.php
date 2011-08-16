<?php


/**
 * Make a connection to the database, and execute a query
 */
function runq($query) {
	//open a connection to the database
	$conn = pg_connect("host=".Conf::$dbhost." port=5432 dbname=".Conf::$dbname." user=".Conf::$dbuser." password=".Conf::$dbpass."");

	//execute the query using our db_query() function
	$return = db_query($conn, $query);

	//close the connection
	pg_close($conn);

	//return the results of the query
	return $return;
}

/**
 * Execute an SQL query
 */
function db_query($conn, $query) {
	//execute the query
	$result = pg_query($conn, $query);

	//something went wrong
	if ($result === false) {
		//throw an exception with the error, and the query that caused it.
		throw new Exception(pg_last_error($conn));
	}

	//if we're not expecting results from the database
	if (stripos(trim($query), "select") !== 0) {
		//then return true to show that the query was successful
		return true;
	}

	//get all the results
	$return = pg_fetch_all($result);

	//return the results of the query
	return $return;
}


/**
 * Postgres specific string escape
 */
function db_escape($string) {
	return pg_escape_string($string);
}

/**
 * Get the next available value in a sequence
 */
function db_nextval($table, $column) {
	$nextval_query = runq("SELECT nextval('{$table}_{$column}_seq');");

	return $nextval_query[0]['nextval'];
}

/**
 * Postgres specific string concatonation
 *
 * Can be called in the following ways:
 *
 * db_concat("foo", "bar", "baz")
 * db_concat(array("foo", "bar", "baz"))
 * db_concat(array("foo", "bar"), "baz")
 */
function db_concat($argument1orarguments, $argument2=null, $argument3=null) {
	//if we're supplied with an array of strings
	if (is_array($argument1orarguments)) {
		$arguments = $argument1orarguments;

	//or, individual strings
	} else if (is_string($argument1orarguments)) {
		$arguments = array_merge((array)$argument1orarguments, (array)$argument2, (array)$argument3);
	}

	//return something that postgres will understand
	return implode("||", $arguments);
}

/**
 * BORK BORK BORK
 */
function db_cast_bigint($column) {
	return $column."::BIGINT";
}


/**
 * Format boolean values for input to the database
 */
function db_boolean($input) {
	//accept (boolean)TRUE
	if (is_bool($input)) {
		if ($input) {
			return "t";
		}
	//accept (int)1
	} else if (is_integer($input)) {
		if ($input === 1) {
			return "t";
		}
	//accept strings 1, t, true, True, TRUE, etc.
	} else if (is_string($input)) {
		$input = trim(strtolower($input));
		if ($input == "1" || $input == "t" || $input == "true") {
			return "t";
		}
	}

	//everything else is false.
	return "f";
}

?>