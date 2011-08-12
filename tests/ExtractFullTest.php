<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/extract/extractors/full/extract.php");

class ExtractFullTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->extract = new ExtractFull;
	}

	protected function tearDown() {
	}

	public function testcheck_argsSuccess() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = date("Y-m-d H:i:s");
		$source_md5 = md5("somefile");

		$this->extract->check_args($source_path, $source_timestamp, $source_md5);
	}

	public function testcheck_argsNoPath() {
		$source_path = "";
		$source_timestamp = date("Y-m-d H:i:s");
		$source_md5 = md5("somefile");

		try {
			$this->extract->check_args($source_path, $source_timestamp, $source_md5);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testcheck_argsInvalidPath() {
		$source_path = "/somthing/image.jpg";
		$source_timestamp = date("Y-m-d H:i:s");
		$source_md5 = md5("somefile");

		try {
			$this->extract->check_args($source_path, $source_timestamp, $source_md5);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testcheck_argsNoTimestamp() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = "";
		$source_md5 = md5("somefile");

		try {
			$this->extract->check_args($source_path, $source_timestamp, $source_md5);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testcheck_argsInvalidTimestamp() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = "squiggles";
		$source_md5 = md5("somefile");

		try {
			$this->extract->check_args($source_path, $source_timestamp, $source_md5);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testcheck_argsNoHash() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = date("Y-m-d H:i:s");
		$source_md5 = "";

		try {
			$this->extract->check_args($source_path, $source_timestamp, $source_md5);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testcheck_argsInvalidHash() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = date("Y-m-d H:i:s");
		$source_md5 = "bam";

		try {
			$this->extract->check_args($source_path, $source_timestamp, $source_md5);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testget_extract_id() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = date("Y-m-d H:i:s");
		$source_md5 = md5("somefile");

		$this->extract->get_extract_id($source_path, $source_timestamp, $source_md5);

		$this->assertNotEmpty($this->extract->extract_id);

		$extract_full_query = runq("SELECT * FROM extract_processes e INNER JOIN extract_full ef ON (ef.extract_id=e.extract_id) WHERE e.extract_id='".db_escape($this->extract->extract_id)."';");

		$this->assertNotEmpty($extract_full_query);
		$this->assertNotEmpty($extract_full_query[0]);
		$this->assertEquals($this->extract->extract_id, $extract_full_query[0]['extract_id']);
		if (Conf::$dblang == "pgsql") {
			$this->assertEquals("f", $extract_full_query[0]['finished']);
			$this->assertEquals("f", $extract_full_query[0]['failed']);
	/* 		$this->assertEquals("f", $extract_full_query[0]['models']); */
		} else if (Conf::$dblang == "mysql") {
			$this->assertEquals("0", $extract_full_query[0]['finished']);
			$this->assertEquals("0", $extract_full_query[0]['failed']);
	/* 		$this->assertEquals("0", $extract_full_query[0]['models']); */
		}
		$this->assertEquals("full", $extract_full_query[0]['extractor']);
		$this->assertEquals(getmypid(), $extract_full_query[0]['extract_pid']);
		$this->assertEquals($source_path, $extract_full_query[0]['source_path']);
		$this->assertEquals($source_timestamp, $extract_full_query[0]['source_timestamp']);
		$this->assertEquals($source_md5, $extract_full_query[0]['source_md5']);

		runq("DELETE FROM extract_processes WHERE extract_id='".db_escape($this->extract->extract_id)."';");
	}

	public function testget_extractdir() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = date("Y-m-d H:i:s");
		$source_md5 = md5("somefile");

		$this->extract->get_extract_id($source_path, $source_timestamp, $source_md5);

		$this->extract->get_extractdir();

		$this->assertNotEmpty($this->extract->extractdir);

		$this->assertTrue(is_dir($this->extract->extractdir));

		shell_exec("rm -r ".$this->extract->extractdir);
	}

	public function testget_extractuntardir() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = date("Y-m-d H:i:s");
		$source_md5 = md5("somefile");

		$this->extract->get_extract_id($source_path, $source_timestamp, $source_md5);

		$this->extract->get_extractdir();

		$this->extract->get_extractuntardir();

		$this->assertNotEmpty($this->extract->extractuntardir);

		$this->assertTrue(is_dir($this->extract->extractuntardir));

		shell_exec("rm -r ".$this->extract->extractdir);
	}

	public function testget_extractuntardirFail() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = date("Y-m-d H:i:s");
		$source_md5 = md5("somefile");

		$this->extract->get_extract_id($source_path, $source_timestamp, $source_md5);

		try {
			$this->extract->get_extractuntardir();
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testcreate_copy_sql() {
		$table_columns = array("sometable" => array("col1", "col2"));
		$sources = array("sometable");
		$tables = array("dump_%{extract_id}_source");

		$this->extract->extract_id = rand();
		$this->extract->extractdir = "/somewhere";

		$sql = $this->extract->create_copy_sql($table_columns, $sources, $tables);

		$this->assertNotEmpty($sql);

		if (Conf::$dblang == "pgsql") {
			$this->assertEquals("CREATE TABLE dump_{$this->extract->extract_id}_source (\n  col1 TEXT,\n  col2 TEXT\n);\nCOPY dump_{$this->extract->extract_id}_source (col1, col2) FROM '{$this->extract->extractdir}/taboutsometable.sql' DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS $$\\$$;\n\n", $sql);
		} else if (Conf::$dblang == "mysql") {
			$this->assertEquals("CREATE TABLE dump_{$this->extract->extract_id}_source (\n  col1 TEXT,\n  col2 TEXT\n) ENGINE=InnoDB;\nLOAD DATA LOCAL INFILE '{$this->extract->extractdir}/taboutsometable.sql' INTO TABLE dump_{$this->extract->extract_id}_source COLUMNS TERMINATED BY '|' ENCLOSED BY '\\'';\n\n", $sql);
		}
	}

	public function testindex_sql() {
		$models = array("somemodel");
		$model_indexes = array("somemodel" => array("CREATE INDEX dump_%{extract_id}_source_someindex ON dump_%{extract_id}_sourcetable (somecolumn);"));

		$this->extract->extract_id = rand();

		$sql = $this->extract->index_sql($models, $model_indexes);

		$this->assertNotEmpty($sql);

		$this->assertEquals("CREATE INDEX dump_{$this->extract->extract_id}_source_someindex ON dump_{$this->extract->extract_id}_sourcetable (somecolumn);", $sql);
	}

	public function testindex_sqlDuplicates() {
		$models = array("somemodel", "someothermodel");
		$model_indexes = array(
			"somemodel" => array("CREATE INDEX dump_%{extract_id}_source_someindex ON dump_%{extract_id}_sourcetable (somecolumn);"),
			"someothermodel" => array("CREATE INDEX dump_%{extract_id}_source_someindex ON dump_%{extract_id}_sourcetable (somecolumn);")
		);

		$this->extract->extract_id = rand();

		$sql = $this->extract->index_sql($models, $model_indexes);

		$this->assertNotEmpty($sql);

		$this->assertEquals("CREATE INDEX dump_{$this->extract->extract_id}_source_someindex ON dump_{$this->extract->extract_id}_sourcetable (somecolumn);", $sql);
	}
}

?>