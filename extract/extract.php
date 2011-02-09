#!/usr/bin/php5
<?php

$process_id = $_SERVER['argv'][1];

if (empty($process_id)) {
	die("extract process id has not been supplied");
}

//where are the dump files
$dump_path = "/home/user/hotel/extract_processes/".$process_id;

if (!is_dir($dump_path)) {
	die("invalid process id, or could not find process folder in export_processes");
}

//what tables do we need
$required_tables = array("cpgCustomer", "Name", "Address", "EMail", "GroupMember");

//get the file that contains the column names for all the tables
$columnsreffile = file_get_contents("{$dump_path}/taboutUserTableColumns.csv");

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
	$columnsref[$tablename] = $tablecolumns;
}

echo "\n\n";

//loop thorugh tables
foreach ($required_tables as $table) {

	//make sure this is clean
	unset($createtable);

	//clear the destination table
	$createtable .= "DROP TABLE dump_".strtolower($table).";\n";

	//SQL to create table
	$createtable .= "CREATE TABLE dump_".strtolower($table)." (\n";

	//SQL to create each row for table
	foreach ($columnsref[$table] as $tablecolumn) {
		$createtable .= "  ".strtolower($tablecolumn)." TEXT";

		//column definitions are separated by commas
		if ($tablecolumn != end($columnsref[$table])) {
			$createtable .= ",\n";
		}
	}

	//finalise SQL create table definition
	$createtable .= ");\n";

	$createtable .= "\n";

	//SQL to import table data
	$createtable .= "COPY dump_".strtolower($table)." (".implode(", ", $columnsref[$table]).") FROM '{$dump_path}/tabout{$table}.sql' DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS ".'$$\$$'.";\n";

	//output SQL
	//this will normally be written to a file to be applied to the database
	echo $createtable;

	echo "\n";
}

//indexes are written as required
echo "CREATE INDEX dump_cpgcustomer_cpgid ON dump_cpgcustomer (cpgid) WHERE (cpgid='IEA');\n";
echo "CREATE INDEX dump_cpgcustomer_customerid ON dump_cpgcustomer (cast(customerid AS BIGINT)) WHERE (cpgid='IEA');\n";
echo "CREATE INDEX dump_cpgcustomer_custstatusid ON dump_cpgcustomer (custstatusid) WHERE (custstatusid='MEMB');\n";

echo "CREATE INDEX dump_name_customerid ON dump_name (cast(customerid AS BIGINT));\n";

echo "CREATE INDEX dump_address_customerid ON dump_address (cast(customerid AS BIGINT));\n";

echo "CREATE INDEX dump_email_emailtypeid ON dump_email (emailtypeid) WHERE (emailtypeid='INET');\n";
echo "CREATE INDEX dump_email_customerid ON dump_email (cast(customerid AS BIGINT)) WHERE (emailtypeid='INET');\n";

echo "CREATE INDEX dump_groupmember_groupid ON dump_groupmember (groupid) WHERE (groupid='6052');\n";
echo "CREATE INDEX dump_groupmember_customerid ON dump_groupmember (cast(customerid AS BIGINT)) WHERE (groupid='6052');\n";


?>