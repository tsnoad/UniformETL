<?php

require_once("/etc/uniformetl/database.php");

class DatabaseSelectTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		runq("INSERT INTO member_ids (member_id) VALUES ('10000000');");
	}

	protected function tearDown() {
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

	public function testSyntaxError() {
		PHPUnit_Framework_Error_Warning::$enabled = FALSE;

		$query = runq("SELECT * FROM member_ids WHERE quizblorg='10000000';");
		$this->assertFalse($query);

		PHPUnit_Framework_Error_Warning::$enabled = TRUE;

/*
		try {
			$query = runq("SELECT * FROM member_ids WHERE quizblorg='10000000';");
		} catch (PHPUnit_Framework_Error $e) {
			var_dump($query);
			$this->assertFalse($query);

			return;
		}

		$this->fail('An expected exception has not been raised.');
*/
	}
}

?>