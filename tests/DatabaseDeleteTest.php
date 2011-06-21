<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");

class DatabaseDeleteTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
		PHPUnit_Framework_Error_Warning::$enabled = FALSE;
		runq("INSERT INTO member_ids (member_id) VALUES ('10000000');");
	}

	protected function tearDown() {
		error_reporting(E_ALL ^ E_NOTICE);
		PHPUnit_Framework_Error_Warning::$enabled = TRUE;
		runq("DELETE FROM member_ids WHERE member_id='10000000';");
	}

	public function testSuccess() {
		$query = runq("DELETE FROM member_ids WHERE member_id='10000000';");
		$this->assertTrue($query);

		$query = runq("SELECT * FROM member_ids WHERE member_id='10000000';");
		$this->assertEmpty($query);
		$this->assertEmpty($query[0]);
	}

	public function testNotFound() {
		$query = runq("DELETE FROM member_ids WHERE member_id='11000000';");
		$this->assertTrue($query);
	}

	public function testSyntaxError() {
		try {
			$query = runq("DELETE FROM member_ids WHERE quizblorg='10000000';");
		} catch (Exception $e) {
/* 			var_dump($e->getMessage()); */

			return;
		}

		$this->fail('An expected exception has not been raised.');
	}
}

?>