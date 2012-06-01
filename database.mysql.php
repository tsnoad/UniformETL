<?php

function db_format($query) {
	$query = preg_replace("/([^\ \(\)\=]*)::BIGINT/i", "$1", $query);

	$query = str_replace("E'\n'", "'\n'", $query);

	return $query;
}

/**
 * Make a connection to the database, and execute a query
 */
function runq($query) {
	//open a connection to the database
	$conn = mysql_connect(Conf::$dbhost, Conf::$dbuser, Conf::$dbpass);

	//select the database we want
	mysql_select_db(Conf::$dbname, $conn);

	//execute the query using our db_query() function
	$return = db_query($conn, $query);

	//close the connection
	mysql_close($conn);

	//return the results of the query
	return $return;
}

/**
 * Execute an SQL query
 */
function db_query($conn, $query) {
	//make sure the query is correctly formatted to MySQL
	$query = db_format($query);

	//execute the query
	$result = mysql_query($query, $conn);

	//something went wrong
	if ($result === false) {
		//throw an exception with the error, and the query that caused it.
		throw new Exception(mysql_error($conn)."\nFor query: ".$query);
	}

	//if we're not expecting results from the database
	if (stripos(trim($query), "select") !== 0 && stripos(trim($query), "show") !== 0) {
		//then return true to show that the query was successful
		return true;
	}

	//emulate postgres' pg_fetch_all functionality
	//create an array of associative array rows
	while ($row = mysql_fetch_assoc($result)) {
	    $return[] = $row;
	}

	//if the query didn't return any rows, then return false
	if (empty($return)) {
		return false;
	}

	//return the results of the query
	return $return;
}

/**
 * MySQL specific string escape
 */
function db_escape($string) {
	return mysql_escape_string($string);
}

/**
 * Simulate postgres' nextval functionality.
 * This requires that a sequence table be manually created for each column that this function will be used on.
 */
function db_nextval($table, $column) {
	//get the largest value in the specified column
	//and the last reserved value from the sequence table
	$nextval_query = runq("select coalesce(max({$column}), 0) as current_val from {$table} union select current_val from {$table}_{$column}_seq;");

	//if the largest and last reserved vals are the same, the union query will only return one row
	if (count($nextval_query) == 1) {
		//the next value is the last plus one
		$next_val = $nextval_query[0]['current_val'] + 1;

		//update the sequence table so this value doesn't get used again
		runq("UPDATE {$table}_{$column}_seq SET current_val='".db_escape($next_val)."'");

	//largest and last reserved vals are different
	//then someone's either reserved a value and not used it yet,
	//or executed an insert without using db_nextval() first
	} else {
		//the largest value from the specified column
		$max_val = $nextval_query[0]['current_val'];
		//the last reserved value from the sequence table
		$last_res_val = $nextval_query[1]['current_val'];

		//next value is whichever is greater, plus one
		$next_val = max($max_val, $last_res_val) + 1;

		//update the sequence table so this value doesn't get used again
		runq("UPDATE {$table}_{$column}_seq SET current_val='".db_escape($next_val)."'");
	}

	return $next_val;
}

/**
 * MySQL specific string concatonation
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

	//return something that mysql will understand
	return "CONCAT(".implode(", ", $arguments).")";
}

/**
 * BORK BORK BORK
 */
function db_cast_bigint($column) {
	return $column;
}

/**
 * Format boolean values for input to the database
 *
 * We need to do this because MySQL can be picky: eg not accepting (string)'t' as valid boolean value 
 */
function db_boolean($input) {
	//accept (boolean)TRUE
	if (is_bool($input)) {
		if ($input) {
			return "1";
		}
	//accept (int)1
	} else if (is_integer($input)) {
		if ($input === 1) {
			return "1";
		}
	//accept strings 1, t, true, True, TRUE, etc.
	} else if (is_string($input)) {
		$input = trim(strtolower($input));
		if ($input == "1" || $input == "t" || $input == "true") {
			return "1";
		}
	}

	//everything else is false.
	return "0";
}

?>