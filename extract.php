#!/usr/bin/php5
<?php

$columnsref = file_get_contents("/home/user/hotel/dumps/taboutUserTableColumns.csv");

echo "\n\n";

foreach (array("cpgCustomer", "Name", "Address", "EMail", "GroupMember") as $table) {
	foreach (explode("\n", $columnsref) as $columnsref_column) {
		$tablecolumns = explode(",", $columnsref_column);

		if (array_shift($tablecolumns) != $table) {
			continue;
		}

		break;
	}

	unset($createtable);

	$createtable .= "DROP TABLE dump_".strtolower($table).";\n";

	$createtable .= "CREATE TABLE dump_".strtolower($table)." (\n";

	foreach ($tablecolumns as $tablecolumn) {
		$createtable .= "  ".strtolower($tablecolumn)." TEXT,\n";
	}

	$createtable = rtrim($createtable, ",\n");
	$createtable .= "\n";
	
	$createtable .= ");\n";

	$createtable .= "\n";



	$createtable .= "COPY dump_".strtolower($table)." (";

	foreach ($tablecolumns as $tablecolumn) {
		$createtable .= strtolower($tablecolumn).", ";
	}
	$createtable = rtrim($createtable, ", ");

	$createtable .= ") FROM '/home/user/hotel/dumps/tabout{$table}.sql' DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS ".'$$\$$'.";\n";

	echo $createtable;

	echo "\n";
}

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