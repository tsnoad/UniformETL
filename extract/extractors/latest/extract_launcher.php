<?php

/* php -r 'require("/etc/uniformetl/extract/extractors/latest/extract_launcher.php"); $extract = New ExtractLatest; $extract->start_extract();' */

require_once("/etc/uniformetl/autoload.php");
require_once("/etc/uniformetl/database.php");

Class ExtractLatest {
	public $extract_id;
	public $required_tables;
	public $sybase;
	public $sybase_out;
	public $sql_file;

	public $sybase_data_structure = array(
		"Customer" => array(
			"CustomerId" => "CustomerId",
			"Field1" => "Sex",
			"Field2" => "DOB",
		),
		"CPGCustomer" => array(
			"CustomerId" => "CustomerId",
			"Field1" => "DivisionID",
			"Field2" => "GradeID",
			"Field3" => "CPGID",
			"Field4" => "CustStatusID",
		),
		"Address" => array(
			"CustomerId" => "CustomerId",
			"Field1" => "AddrTypeID",
			"Field2" => "Line1",
			"Field3" => "Line2",
			"Field4" => "Line3",
			"Field5" => "Suburb",
			"Field6" => "State",
			"Field7" => "Postcode",
			"Field8" => "CountryId",
			"Field9" => "Valid",
		),
		"GroupMember" => array(
			"CustomerId" => "CustomerId",
			"Field1" => "GroupID",
			"Field2" => "SubGroupID",
			"Field3" => "RetirementDate",
		),
		"Email" => array(
			"CustomerId" => "CustomerId",
			"Field1" => "EmailAddress",
			"Field2" => "EmailTypeID",
		),
		"Name" => array(
			"CustomerId" => "CustomerId",
			"Field1" => "NameTypeID",
			"Field2" => "NameLine2",
			"Field3" => "NameLine1",
		),
	);

	public $table_columns = array(
		"Customer" => array(
			"CustomerId", "CustTypeId", "Sex", "DOB",
		),
		"cpgCustomer" => array(
			"CustomerId", "DivisionID", "GradeID", "CPGID", "CustStatusID", "supppnenabled", "finstatus",
		),
		"Address" => array(
			"CustomerId", "AddrTypeID", "Line1", "Line2", "Line3", "Suburb", "State", "Postcode", "CountryId", "Valid",
		),
		"GroupMember" => array(
			"CustomerId", "GroupID", "SubGroupID", "RetirementDate",
		),
		"EMail" => array(
			"CustomerId", "EmailAddress", "EmailTypeID",
		),
		"Name" => array(
			"CustomerId", "NameTypeID", "NameLine2", "NameLine1",
		),
		"GradeHistory" => array("CustomerId","cpgid", "gradetypeid","gradeid", "datechange", "changereasonid",
		),
		"cpgGradeType" => array(
			"CustomerId", "GradeTypeId", "classid",
		),
	);

	function start_extract() {
		$this->check_already_extracting();
		$this->check_already_transforming();

		$previous_extract = runq("SELECT * FROM extract_processes WHERE extractor='latest' AND finished=TRUE ORDER BY finish_date DESC LIMIT 1;");

		if (!empty($previous_extract)) {
			$time_since = time() - strtotime($previous_extract[0]['finish_date']);
			
			if ($time_since < 3600) {
				die("latest extract was run {$time_since}s seconds ago. need to wait longer between latest extracts...");
			}
		}


		$models = New Models;
		$models->start();

		//reserve an extract id, and create an extract process in the database
		$this->get_extract_id();
		//create the folders where we're going to put the files we're working on
		$this->get_extractdir();

		//what indexes will we need to create. ask each model
		$model_indexes = Plugins::hook("extract_index-sql", array());

		$this->start_sybase();
		
		list($member_ids, $source_data) = $this->get_member_data($this->table_columns, $models->sources);
		
		$this->close_sybase();

		$this->save_member_ids($member_ids);

		$sql = $this->create_copy_sql($source_data, $this->table_columns, $models->sources, $models->tables);

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
	
	function get_member_data($table_columns, $sources) {
		$sources_casei = array_combine(array_map("strtolower", $sources), $sources);

		$result = sybase_query("execute spAPIEAAuthenticationSelect @pLastMinutesNum = 360", $this->sybase);

		while ($row = sybase_fetch_assoc($result)) {
			$member_id = trim($row['CustomerId']);
			$source_table = trim($row['TableName']);

			$source = $sources_casei[strtolower($source_table)];

			if (!in_array(strtolower($source_table), array_keys($sources_casei))) {
				continue;
			}

			$member_ids[$member_id] = $member_id;

			unset($source_data_row);

			foreach ($table_columns[$source] as $table_column) {
				$sybase_row_name = array_search($table_column, $this->sybase_data_structure[$source_table]);

				if (empty($row[$sybase_row_name])) {
					if ($table_column == "CustTypeId") {
						$source_data_row[$table_column] = "INDI";
					} else {
						$source_data_row[$table_column] = "";
					}
				} else {
					if ($table_column == "DOB") {
						$source_data_row[$table_column] = date("M d Y g:i:s:000A", strtotime($row[$sybase_row_name]));
					} else {
						$source_data_row[$table_column] = $row[$sybase_row_name];
					}
				}
			}

			$source_data[$source][] = $source_data_row;
		}

		return array($member_ids, $source_data);
	}

	function create_copy_sql($source_data, $table_columns, $sources, $tables) {
		//loop through all the sources we need
		foreach ($sources as $i => $source) {
			//what's the name of the table we're going to create
			//table names include the extract id
			$table = str_replace("%{extract_id}", $this->extract_id, $tables[$i]);

			//create table
			$sql .= db_choose(
				db_pgsql("CREATE TABLE {$table} (\n  ".implode(" TEXT,\n  ", $table_columns[$source])." TEXT\n);\n"), 
				db_mysql("CREATE TABLE {$table} (\n  ".implode(" TEXT,\n  ", $table_columns[$source])." TEXT\n) ENGINE=InnoDB;\n")
			);

			//import from source file
			$sql .= db_choose(
				db_pgsql("COPY {$table} (".implode(", ", $table_columns[$source]).") FROM STDIN DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS ".'$$\$$'.";\n"),
				db_mysql("pfeh;\n")
			);


			if (!empty($source_data[$source])) {
				foreach ($source_data[$source] as $source_row) {
					$sql .= "'".utf8_encode(implode("'|'", array_map("db_escape", $source_row)))."'\n";
				}
			}

			$sql .= "\\.";
			$sql .= "\n\n";
		}

		return $sql;
	}
}

?>