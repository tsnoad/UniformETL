<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/janitor/janitor.php");

class JanitorGetTablesTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->janitor = new Janitor;

		runq("CREATE TABLE dump_1000000_testtesttest (somecolumn TEXT, someothercolumn TEXT);");
	}

	protected function tearDown() {
		runq("DROP TABLE dump_1000000_testtesttest;");
	}

	public function testSuccess() {
		$finished_tables = $this->janitor->get_finished_tables(array("1000000"));

		$this->assertTrue(is_array($finished_tables));
		$this->assertEquals(array("dump_1000000_testtesttest"), $finished_tables);
	}
}

?>