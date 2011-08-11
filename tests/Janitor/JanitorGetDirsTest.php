<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/janitor/janitor.php");

class JanitorGetDirsTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->janitor = new Janitor;

		mkdir(Conf::$software_path."extract/extract_processes/1000000");
	}

	protected function tearDown() {
		rmdir(Conf::$software_path."extract/extract_processes/1000000");
	}

	public function testSuccess() {
		$finished_dirs = $this->janitor->get_finished_dirs(array("1000000"));

		$this->assertTrue(is_array($finished_dirs));
		$this->assertEquals(array("1000000"), $finished_dirs);
	}
}

?>