<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");

class DatabaseSelectTest extends PHPUnit_Framework_TestCase {
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
		$query = runq("SELECT * FROM member_ids WHERE member_id='10000000';");
		$this->assertNotEmpty($query);
		$this->assertNotEmpty($query[0]);
		$this->assertEquals("10000000", $query[0]['member_id']);
	}

	public function testEmpty() {
		$query = runq("SELECT * FROM member_ids WHERE member_id='10000001';");
		$this->assertFalse($query);
	}

	public function testColumnSyntaxError() {
		try {
			$query = runq("SELECT * FROM member_ids WHERE quizblorg='10000000';");
		} catch (Exception $e) {
/* 			var_dump($e->getMessage()); */

			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testTableSyntaxError() {
		try {
			$query = runq("SELECT * FROM quizblorg WHERE member_id='10000000';");
		} catch (Exception $e) {
/* 			var_dump($e->getMessage()); */

			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testNextvalSuccess() {
		$query = runq("SELECT nextval('member_ids_id_seq');");
		$this->assertNotEmpty($query);
		$this->assertNotEmpty($query[0]);
		$this->assertNotEmpty($query[0]['nextval']);
	}

	public function testNextvalBadSyntax() {
		try {
			$query = runq("SELECT nextval('squiggles');");
		} catch (Exception $e) {
/* 			var_dump($e->getMessage()); */

			return;
		}

		$this->fail('An expected exception has not been raised.');
	}
}

?>