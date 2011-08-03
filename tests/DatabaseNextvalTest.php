<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");

class DatabaseNextvalTest extends PHPUnit_Framework_TestCase {
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

	public function testNextvalSuccess() {
		$nextval = db_nextval("member_ids", "id");
		$this->assertNotEmpty($nextval);
	}

	public function testNextvalBadSyntax() {
		try {
			$query = db_nextval("squiggly", "squiggle");
		} catch (Exception $e) {
/* 			var_dump($e->getMessage()); */

			return;
		}

		$this->fail('An expected exception has not been raised.');
	}
}

?>