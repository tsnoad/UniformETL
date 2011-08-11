<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/janitor/janitor.php");

class JanitorDropTableTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->janitor = new Janitor;
	}

	protected function tearDown() {
	}

	public function testSuccess() {
		runq("CREATE TABLE dump_1000000_testtesttest (somecolumn TEXT, someothercolumn TEXT);");

		$this->janitor->drop_table("dump_1000000_testtesttest");

		$table_query = runq("select tablename from pg_tables where tablename='".db_escape("dump_1000000_testtesttest")."';");
		$this->assertEmpty($table_query, "table was not deleted");
	}

	public function testBadName() {
		try {
			$this->janitor->drop_table("dump_squiggle_testtesttest");
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testBadName2() {
		try {
			$this->janitor->drop_table("dump_1000000");
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}
}

?>