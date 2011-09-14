<?php

require_once("/etc/uniformetl/autoload.php");
require_once("/etc/uniformetl/database.php");

Class ExtractLatest {
	public $extract_id;
	public $required_tables;
	public $sybase;
	public $sybase_out;
	public $sql_file;

	function start() {
		$this->check_already_extracting();
		$this->check_already_transforming();

		$previous_extract = runq("SELECT * FROM extract_processes WHERE extractor='latest' AND finished=TRUE ORDER BY finish_date DESC LIMIT 1;");

		if (!empty($previous_extract)) {
			$time_since = time() - strtotime($previous_extract[0]['finish_date']);
			
			if ($time_since < 3600) {
				print_r("latest extract was run {$time_since}s seconds ago. need to wait longer between latest extracts...");
				return;
			}
		}

		$models = New Models;
		$models->start();

		$sybase_data_structure = SybaseDataStructures::$structures[Conf::$sybasestruct];

		//reserve an extract id, and create an extract process in the database
		$this->get_extract_id();
		//create the folders where we're going to put the files we're working on
		$this->get_extractdir();

		//what indexes will we need to create. ask each model
		$model_indexes = Plugins::hook("extract_index-sql", array());

		$this->start_sybase();
		
		$member_data = $this->get_member_data($models->columns, $models->sources, $sybase_data_structure);
		
		$this->close_sybase();

		$member_data = $this->filter_tables($member_data, $sybase_data_structure, $models->sources);

		$member_data = $this->filter_columns($member_data, $sybase_data_structure, $models->columns);

		$member_ids = $this->get_member_ids($member_data);

		$this->save_member_ids($member_ids);

		$sql = $this->create_copy_sql($member_data, $models->sources, $models->columns, $models->tables);

var_dump($sql);

		//write the sql to a file
		file_put_contents($this->extractdir."/dump.sql", $sql);

		//run the sql file against the database
		if (Conf::$dblang == "pgsql") {
			passthru("psql ".Conf::$dbname." < {$this->extractdir}/dump.sql 2>&1", $return_state);

			var_dump($return_state);
		} else if (Conf::$dblang == "mysql") {
			passthru("mysql -u ".Conf::$dbuser." -p".Conf::$dbpass." ".Conf::$dbname." < {$this->extractdir}/dump.sql 2>&1");
		}
		
		try {
			//let the database know we've finished the extract
			runq("UPDATE extract_processes SET finished=TRUE, finish_date=now() WHERE extract_id='".db_escape($this->extract_id)."';");
		} catch (Exception $e) {
			die($e->getMessage());
		}
	}

	function check_already_extracting() {
		if (trim(shell_exec("ps h -C run_extract_launcher.php o pid | wc -l")) > 1) {
			die("extract launcher is currently running");
		}

		$already_extracting = runq("SELECT count(*) FROM extract_processes WHERE finished=FALSE;");

		if ($already_extracting[0]['count'] > 0) {
			die("extract is currently running");
		}
	}

	function check_already_transforming() {
		$already_transforming = runq("SELECT count(*) FROM transform_processes WHERE finished=FALSE;");

		if ($already_transforming[0]['count'] > 0) {
			die("transform is currently running");
		}
	}

	/**
	 * Open a connection to the sybase server
	 */
	function start_sybase() {
		//connect to the sybase server, and switch to the right database
		$this->sybase = sybase_connect(Conf::$sybasedbalias, Conf::$sybasedbuser, Conf::$sybasedbpass, "ISO-8859-1");
		sybase_select_db(Conf::$sybasedbname, $this->sybase);
	}

	/**
	 * Close the connection with the sybase server
	 */
	function close_sybase() {
		sybase_close($this->sybase);
	}

	/*
	 * reserve an extract id, and create an extract process in the database
	 */
	function get_extract_id() {
		try {
			//reserve a process id so other methods can use it
			$this->extract_id = db_nextval("extract_processes", "extract_id");
		
			//create a process in the database
			runq("INSERT INTO extract_processes (extract_id, extractor, extract_pid, models) VALUES ('".db_escape($this->extract_id)."', 'latest', '".db_escape(getmypid())."', '".db_escape(json_encode(Conf::$do_transforms))."');");
		
			//record information about the source file
			runq("INSERT INTO extract_latest (extract_id, member_ids) VALUES ('".db_escape($this->extract_id)."', '".db_escape("")."');");

		} catch (Exception $e) {
			print_r($e->getMessage());
			die("could not create process in database");
		}
	}

	/*
	 * Create a folder where we can put the source files while we work on them
	 */
	function get_extractdir() {
		//so other methods know where the folder is
		$this->extractdir = Conf::$software_path."extract/extract_processes/{$this->extract_id}";

		//create the folder
		if (!mkdir($this->extractdir)) {
			die("could not create dump folder");
		}
	}

	function save_member_ids($member_ids) {
		try {
			runq("UPDATE extract_latest SET member_ids='".db_escape(json_encode(array_values($member_ids)))."' WHERE extract_id='".db_escape($this->extract_id)."';");
		} catch (Exception $e) {
			print_r($e->getMessage());
			die("could not save process");
		}
	}
	
	function get_member_data($source_columns, $sources, $sybase_data_structure) {
		$result = sybase_query("execute spAPIEAAuthenticationSelect @pLastMinutesNum = 360", $this->sybase);

		while ($row = sybase_fetch_assoc($result)) {
			$source_table = trim($row['TableName']);

			$member_data[$source_table][] = $row;
		}

		return $member_data;
	}

	function filter_tables($member_data, $structure, $sources) {
		$data_table_names = array_combine(array_keys($member_data), array_map("strtolower", array_keys($member_data)));

		foreach ($sources as $source) {
			$data_table_name = array_search(strtolower($source), $data_table_names);

			if (empty($member_data[$data_table_name])) {
				$data_out[$source] = array();
				continue;
			}

			foreach ($member_data[$data_table_name] as $row) {
				$data_out[$source][] = $row;
			}
		}

		return $data_out;
	}

	function filter_columns($member_data, $structure, $source_columns) {
		$struc_table_names = array_combine(array_keys($structure), array_map("strtolower", array_keys($structure)));

		foreach ($member_data as $table => $rows) {
			$struc_table_name = array_search(strtolower($table), $struc_table_names);

			unset($struc_column_names);
			$struc_column_names = array_map("strtolower", $structure[$struc_table_name]);

			if (empty($rows)) {
				continue;
			}

			foreach ($rows as $row) {
				unset($data_row_out);

				foreach ($source_columns[$table] as $column) {
					$column_name = array_search(strtolower($column), $struc_column_names);

					if (strtolower($column) == "custtypeid") {
						$data_row_out[$column] = "INDI";
						continue;
					} else if (strtolower($column) == "dob") {
						unset($date_formatted);
						$date_formatted = preg_replace("/^([0-9][0-9]?)\/([0-9][0-9]?)\/([0-9][0-9][0-9]?[0-9]?)$/", '$3-$2-$1', $row[$column_name]);

						if (!empty($date_formatted)) {
							$date_formatted = date("M d Y 12:00:00:000\A\M", strtotime($date_formatted));
						}

						$data_row_out[$column] = $date_formatted;
						continue;
					}

					$data_row_out[$column] = $row[$column_name];
				}

				$data_out[$table][] = $data_row_out;
			}
		}

		return $data_out;
	}

	function get_member_ids($member_data) {
		foreach ($member_data as $table => $rows) {
			if (strtolower($table) != "customer") {
				continue;
			}

			foreach ($rows as $columns) {
				foreach ($columns as $column => $data) {
					if (strtolower($column) == "customerid") {
						$member_id = trim($data);
						$member_ids[] = $member_id;

						continue;
					}
				}
			}
		}

		$member_ids = array_unique($member_ids);

		return $member_ids;
	}

	function create_copy_sql($source_data, $sources, $source_columns, $tables) {
		//loop through all the sources we need
		foreach ($sources as $i => $source) {
			//what's the name of the table we're going to create
			//table names include the extract id
			$table = str_replace("%{extract_id}", $this->extract_id, $tables[$i]);

			$sql .= $this->create_sql($table, $source_columns[$source]);
			$sql .= $this->copy_sql($table, $source_columns[$source], $source_data[$source]);
		}

		return $sql;
	}

	function create_sql($table, $columns) {
		//create table
		$sql = db_choose(
			db_pgsql("CREATE TABLE {$table} (\n  ".implode(" TEXT,\n  ", $columns)." TEXT\n);\n"), 
			db_mysql("CREATE TABLE {$table} (\n  ".implode(" TEXT,\n  ", $columns)." TEXT\n) ENGINE=InnoDB;\n")
		);
		return $sql;
	}

	function copy_sql($table, $columns, $data) {
		//import from source file
		$sql = db_choose(
			db_pgsql("COPY {$table} (".implode(", ", $columns).") FROM STDIN DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS ".'$$\$$'.";\n"),
			db_mysql("pfeh;\n")
		);

		if (!empty($data)) {
			foreach ($data as $source_row) {
				unset($data_array);

				foreach ($columns as $column) {
					$data_array[] = "'".utf8_encode(db_escape($source_row[$column]))."'";
				}

				$sql .= implode("|", $data_array)."\n";
			}
		}

		$sql .= "\\.";
		$sql .= "\n\n";

		return $sql;
	}
}

?>