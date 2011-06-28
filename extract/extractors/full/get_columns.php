<?php

require_once("/etc/uniformetl/autoload.php");

class ExtractFullGetColumns {
	function start($columnsfile, $sources) {
		if (empty($columnsfile) || !is_file($columnsfile)) {
			die("columns file was not provided");
		}

		$data = file_get_contents($columnsfile);

		$lines = explode("\n", $data);

		foreach ($lines as $line) {
			$table = substr($line, 0, strpos($line, ","));

			if (strpos($table, "v") === 0) {
				$table = substr($table, 1);
			}

			if (!in_array($table, $sources)) {
				continue;
			}

			$line = substr($line, 0, strpos($line, "*$%#"));

			$columnsdata = substr($line, strpos($line, ",") + 1);

			$columns = explode(",", $columnsdata);

			$table_columns[$table] = $columns;
		}

		return $table_columns;
	}
}

?>