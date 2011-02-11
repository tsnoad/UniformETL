#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/config.php");

class Models {
	public $required_transforms = array(
		"addresses" => array("member_ids"),
		"confluence_statuses" => array("member_ids", "names", "emails", "passwords", "web_statuses"),
		"ecpd_statuses" => array("member_ids"),
		"emails" => array("member_ids"),
		"member_ids" => array(),
		"names" => array("member_ids"),
		"passwords" => array("member_ids"),
		"web_statuses" => array("member_ids")
	);

	public $required_tables = array(
		"addresses" => array("dump_address"),
		"confluence_statuses" => array(),
		"ecpd_statuses" => array("dump_groupmember"),
		"emails" => array("dump_email"),
		"member_ids" => array("dump_cpgcustomer"),
		"names" => array("dump_name"),
		"passwords" => array(),
		"web_statuses" => array("dump_cpgcustomer")
	);
	
	public $transform_priority = array(
		"addresses" => "secondary",
		"confluence_statuses" => "tertiary",
		"ecpd_statuses" => "secondary",
		"emails" => "secondary",
		"member_ids" => "primary",
		"names" => "secondary",
		"passwords" => "secondary",
		"web_statuses" => "secondary"
	);
	
	//indexes are written as required
/*
	public $indexes = array(
		"addresses" => array("CREATE INDEX dump_address_customerid ON dump_address (cast(customerid AS BIGINT));"),
		"confluence_statuses" => array(),
		"ecpd_statuses" => array(
			"CREATE INDEX dump_groupmember_groupid ON dump_groupmember (groupid) WHERE (groupid='6052');",
			"CREATE INDEX dump_groupmember_customerid ON dump_groupmember (cast(customerid AS BIGINT)) WHERE (groupid='6052');"
		),
		"emails" => array(
			"CREATE INDEX dump_email_emailtypeid ON dump_email (emailtypeid) WHERE (emailtypeid='INET');",
			"CREATE INDEX dump_email_customerid ON dump_email (cast(customerid AS BIGINT)) WHERE (emailtypeid='INET');"
		),
		"member_ids" => array(
			"CREATE INDEX dump_cpgcustomer_cpgid ON dump_cpgcustomer (cpgid) WHERE (cpgid='IEA');",
			"CREATE INDEX dump_cpgcustomer_customerid ON dump_cpgcustomer (cast(customerid AS BIGINT)) WHERE (cpgid='IEA');",
			"CREATE INDEX dump_cpgcustomer_custstatusid ON dump_cpgcustomer (custstatusid) WHERE (custstatusid='MEMB');"
		),
		"names" => array("CREATE INDEX dump_name_customerid ON dump_name (cast(customerid AS BIGINT));"),
		"passwords" => array(),
		"web_statuses" => array()
	);
*/
	
	public $dump_table_sources = array(
		"dump_address" => "Address",
		"dump_cpgcustomer" => "cpgCustomer",
		"dump_email" => "EMail",
		"dump_groupmember" => "GroupMember",
		"dump_name" => "Name"
	);
}

class Foobar {
	public $transforms;
	public $tables;
	public $sources;

	function start() {
		$this->models = New Models;

		$this->get_all_transforms();
		$this->get_all_tables();
		$this->get_all_source_tables();

		print_r($this->transforms);
		print_r($this->tables);
		print_r($this->sources);
	}

	function get_all_transforms() {
		foreach ($this->conf->do_transforms as $transform) {
			$this->transforms[] = $transform;
		
			foreach ($this->models->required_transforms[$transform] as $required_transform) {
				$this->transforms[] = $required_transform;
			}
		}
		
		$this->transforms = array_unique($this->transforms);
		
		foreach ($this->transforms as $transform) {
			$transforms_sort_args[] = array("priority" => $this->models->transform_priority[$transform]);
		}
		
		array_multisort($transforms_sort_args, $this->transforms);
	}

	function get_all_tables() {
		foreach ($this->transforms as $transform) {
			foreach ($this->models->required_tables[$transform] as $required_table) {
				$this->tables[] = $required_table;
			}
		}
		
		$this->tables = array_unique($this->tables);
	}

	function get_all_source_tables() {
		foreach ($this->tables as $table) {
			$this->sources[] = $this->models->dump_table_sources[$table];
		}
	}
}

class Extractor {
	public $conf;
	public $process_id;
	public $columnsref;
	public $sql_file;

	function start() {
		$this->conf = New Conf;
		$this->conf->do_transforms = array("member_ids", "passwords", "names", "emails", "addresses", "web_statuses", "ecpd_statuses", "confluence_statuses");

		$this->foobar = New Foobar;
		$this->foobar->conf = $this->conf;
		$this->foobar->start();

		$this->check_process_id();

		//where are the dump files
		$this->dump_path = $this->conf->dump_path.$this->process_id;

		$this->check_dump_path();

		$this->output_file = $this->dump_path."/dump.sql";

		file_put_contents($this->output_file, "");

		//what tables do we need
		$required_tables = $this->foobar->sources;
	
		$this->create_column_reference();

		file_put_contents($this->output_file, "\n\n", FILE_APPEND);

		//loop thorugh tables
		foreach ($required_tables as $table) {

			$this->output_drop_table($table);

			$this->output_create_table($table);

			$this->output_copy_table($table);
		
			file_put_contents($this->output_file, "\n", FILE_APPEND);
		}
		
		$this->output_indexes();
	}

	function check_process_id() {
		if (empty($this->process_id)) {
			die("extract process id has not been supplied");
		}
	}

	function check_dump_path() {
		if (!is_dir($this->dump_path)) {
			die("could not find process folder in export_processes");
		}
	}

	function create_column_reference() {
		if (!is_file("{$this->dump_path}/taboutUserTableColumns.csv")) {
/* 			die("invalid process id, or could not find process folder in export_processes"); */
		}

		//get the file that contains the column names for all the tables
		$columnsreffile = file_get_contents("{$this->dump_path}/taboutUserTableColumns.csv");

		//each table has it's own row
		//the first column is the table name
		//then column names separated by commas
		foreach (explode("\n", $columnsreffile) as $columnsref_column) {
			//skip empty lines
			if (empty($columnsref_column)) continue;
		
			//separate the row
			$tablecolumns = explode(",", $columnsref_column);
		
			//get the table name
			$tablename = array_shift($tablecolumns);
		
			//place the array of column names into an array
			//and index with the table name
			$this->columnsref[$tablename] = $tablecolumns;
		}
	}

	function output_drop_table($table) {
		//clear the destination table
		$dump_sql = "DROP TABLE dump_".strtolower($table).";\n";

		file_put_contents($this->output_file, $dump_sql, FILE_APPEND);
	}

	function output_create_table($table) {
		//SQL to create table
		$dump_sql .= "CREATE TABLE dump_".strtolower($table)." (\n";
	
		//SQL to create each row for table
		foreach ($this->columnsref[$table] as $tablecolumn) {
			$dump_sql .= "  ".strtolower($tablecolumn)." TEXT";
	
			//column definitions are separated by commas
			if ($tablecolumn != end($this->columnsref[$table])) {
				$dump_sql .= ",\n";
			}
		}
	
		//finalise SQL create table definition
		$dump_sql .= ");\n";
	
		$dump_sql .= "\n";

		file_put_contents($this->output_file, $dump_sql, FILE_APPEND);
	}

	function output_copy_table($table) {
		//SQL to import table data
		$dump_sql = "COPY dump_".strtolower($table)." (".implode(", ", $this->columnsref[$table]).") FROM '{$this->dump_path}/tabout{$table}.sql' DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS ".'$$\$$'.";\n";

		file_put_contents($this->output_file, $dump_sql, FILE_APPEND);
	}

	function output_indexes() {
		$dump_sql = "CREATE INDEX dump_cpgcustomer_cpgid ON dump_cpgcustomer (cpgid) WHERE (cpgid='IEA');\n";
		$dump_sql .= "CREATE INDEX dump_cpgcustomer_customerid ON dump_cpgcustomer (cast(customerid AS BIGINT)) WHERE (cpgid='IEA');\n";
		$dump_sql .= "CREATE INDEX dump_cpgcustomer_custstatusid ON dump_cpgcustomer (custstatusid) WHERE (custstatusid='MEMB');\n";
		
		$dump_sql .= "CREATE INDEX dump_name_customerid ON dump_name (cast(customerid AS BIGINT));\n";
		
		$dump_sql .= "CREATE INDEX dump_address_customerid ON dump_address (cast(customerid AS BIGINT));\n";
		
		$dump_sql .= "CREATE INDEX dump_email_emailtypeid ON dump_email (emailtypeid) WHERE (emailtypeid='INET');\n";
		$dump_sql .= "CREATE INDEX dump_email_customerid ON dump_email (cast(customerid AS BIGINT)) WHERE (emailtypeid='INET');\n";
		
		$dump_sql .= "CREATE INDEX dump_groupmember_groupid ON dump_groupmember (groupid) WHERE (groupid='6052');\n";
		$dump_sql .= "CREATE INDEX dump_groupmember_customerid ON dump_groupmember (cast(customerid AS BIGINT)) WHERE (groupid='6052');\n";

		file_put_contents($this->output_file, $dump_sql, FILE_APPEND);
	}
}


$extractor = New Extractor;
$extractor->process_id = $_SERVER['argv'][1];
$extractor->start();



?>