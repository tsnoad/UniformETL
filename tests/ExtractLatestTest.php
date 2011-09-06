<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/extract/extractors/latest/extract_launcher.php");

class ExtractLatestTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->extract = new ExtractLatest;
	}

	protected function tearDown() {
	}

	public function testfilter_tables() {
		$structure = array(
			"FOObar" => array("col1" => "foo", "col2" => "bar"),
			"BARfoo" => array("col1" => "baz", "col2" => "fop")
		);
		$sources = array("foobar", "barfoo", "foobarfoo");
		$member_data = array(
			"FOObar" => array(
				array("col1" => "one", "col2" => "two"),
			),
			"BARfoo" => array(
				array("col1" => "three", "col2" => "four")
			)
		);

		$expected = array(
			"foobar" => array(
				array("col1" => "one", "col2" => "two"),
			),
			"barfoo" => array(
				array("col1" => "three", "col2" => "four")
			),
			"foobarfoo" => array()
		);

		$foobar = $this->extract->filter_tables($member_data, $structure, $sources);

		$this->assertEquals($expected, $foobar);
	}


	public function testfilter_columns() {
		$structure = array(
			"FOObar" => array("col1" => "foo", "col2" => "bar"),
			"BARfoo" => array("col1" => "baz", "col2" => "fop")
		);
		$source_columns = array(
			"foobar" => array("foo", "bar"), 
			"barfoo" => array("baz", "fop")
		);
		$member_data = array(
			"foobar" => array(
				array("col1" => "one", "col2" => "two"),
				array("col1" => "three", "col2" => "four")
			),
			"barfoo" => array(
				array("col1" => "five", "col2" => "six"),
				array("col1" => "seven")
			)
		);

		$expected = array(
			"foobar" => array(
				array("foo" => "one", "bar" => "two"),
				array("foo" => "three", "bar" => "four")
			),
			"barfoo" => array(
				array("baz" => "five", "fop" => "six"),
				array("baz" => "seven", "fop" => "")
			)
		);

		$foobar = $this->extract->filter_columns($member_data, $structure, $source_columns);

		$this->assertEquals($expected, $foobar);
	}

	public function testget_member_ids() {
		$member_data = array(
			"customer" => array(
				array("foo" => "one", "customerid" => "10000001"),
				array("foo" => "three", "customerid" => "10000002")
			)
		);

		$foobar = $this->extract->get_member_ids($member_data);

		$this->assertEquals(array("10000001", "10000002"), $foobar);
	}

	public function testcreate_sql() {
		$table = "foobar";
		$columns = array("foo", "bar");

		$sql = $this->extract->create_sql($table, $columns);

		$this->assertEquals("CREATE TABLE foobar (\n  foo TEXT,\n  bar TEXT\n);\n", $sql);
	}

	public function testcopy_sql() {
		$table = "foobar";
		$columns = array("foo", "bar");
		$data = array(array("foo" => "1", "bar" => "2"), array("foo" => "1", "bar" => "2"));

		$sql = $this->extract->copy_sql($table, $columns, $data);

		$this->assertEquals(
			"COPY foobar (foo, bar) FROM STDIN DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS ".'$$\$$'.";\n".
			"'1'|'2'\n".
			"'1'|'2'\n".
			"\\.\n\n", 
			$sql
		);
	}
	public function testcopy_sqlUnorderedData() {
		$table = "foobar";
		$columns = array("foo", "bar");
		$data = array(array("bar" => "2", "foo" => "1"), array("bar" => "2", "foo" => "1"));

		$sql = $this->extract->copy_sql($table, $columns, $data);

		$this->assertEquals(
			"COPY foobar (foo, bar) FROM STDIN DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS ".'$$\$$'.";\n".
			"'1'|'2'\n".
			"'1'|'2'\n".
			"\\.\n\n", 
			$sql
		);
	}

	public function testcreate_copy_sql() {
		$this->extract->extract_id = 10000000;
		$source_data = array(
			"foobar" => array(
				array("foo" => "1", "bar" => "2"),
				array("foo" => "3", "bar" => "4")
			), 
			"barfoo" => array(
				array("bar" => "5", "foo" => "6"),
				array("bar" => "7", "foo" => "8")
			)
		);
		$sources = array("foobar", "barfoo");
		$tables = array("foo_%{extract_id}_bar", "bar_%{extract_id}_foo");
		$source_columns = array("foobar" => array("foo", "bar"), "barfoo" => array("bar", "foo"));

		$sql = $this->extract->create_copy_sql($source_data, $sources, $source_columns, $tables);

		$this->assertEquals(
			"CREATE TABLE foo_10000000_bar (\n  foo TEXT,\n  bar TEXT\n);\n".
			"COPY foo_10000000_bar (foo, bar) FROM STDIN DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS ".'$$\$$'.";\n".
			"'1'|'2'\n".
			"'3'|'4'\n".
			"\\.\n\n".
			"CREATE TABLE bar_10000000_foo (\n  bar TEXT,\n  foo TEXT\n);\n".
			"COPY bar_10000000_foo (bar, foo) FROM STDIN DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS ".'$$\$$'.";\n".
			"'5'|'6'\n".
			"'7'|'8'\n".
			"\\.\n\n", 
			$sql
		);
	}
}

?>