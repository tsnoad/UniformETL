<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/janitor/janitor.php");

class JanitorRemoveDirTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->janitor = new Janitor;
	}

	protected function tearDown() {
	}

	public function testSuccess() {
		mkdir(Conf::$software_path."extract/extract_processes/1000000");

		$this->janitor->remove_dir("1000000");

		$dir_query = scandir(Conf::$software_path."extract/extract_processes/");
		$this->assertNotContains("1000000", $dir_query, "dir was not removed");
	}

	public function testBadName() {
		try {
			$this->janitor->remove_dir("boop");
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}
}

?>