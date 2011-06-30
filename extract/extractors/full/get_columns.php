<?php

require_once("/etc/uniformetl/autoload.php");

class ExtractFullGetColumns {
	function start($columnsfile, $sources) {
		//make sure we've been provided with the file that contains the table/column data
		if (empty($columnsfile) || !is_file($columnsfile)) {
			die("columns file was not provided");
		}

		//get everything from the file
		$data = file_get_contents($columnsfile);

		//break into lines
		$lines = explode("\n", $data);

		//for each line
		foreach ($lines as $line) {
			//the first value on each line is the table name
			//what's the name of the table that the columns in this row belong to?
			$table = substr($line, 0, strpos($line, ","));

			//a few table names start with v.
			//this is an anomoly: the v must be removed
			if (strpos($table, "v") === 0) {
				$table = substr($table, 1);
			}

			//if this isn't one of the tables we need
			if (!in_array($table, $sources)) {
				//skip it
				continue;
			}

			//remove the *$%# from the end of the row
			$line = substr($line, 0, strpos($line, "*$%#"));

			//remove the table name from the row
			$columnsdata = substr($line, strpos($line, ",") + 1);

			//convert the comma separated list of values into an array
			$columns = explode(",", $columnsdata);

			//add to the array of tables
			$table_columns[$table] = $columns;
		}

		return $table_columns;
	}
}

?>