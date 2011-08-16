<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");

class DatabaseNextvalTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
		PHPUnit_Framework_Error_Warning::$enabled = FALSE;

		if (Conf::$dblang == "pgsql") {
			runq("CREATE TABLE sequence_test (id BIGSERIAL PRIMARY KEY);");
		} else if (Conf::$dblang == "mysql") {
			runq("CREATE TABLE sequence_test (id BIGINT AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB;");
			runq("CREATE TABLE sequence_test_id_seq (current_val BIGINT) ENGINE=InnoDB;");
			runq("INSERT INTO sequence_test_id_seq (current_val) VALUES (0);");
		}
	}

	protected function tearDown() {
		error_reporting(E_ALL ^ E_NOTICE);
		PHPUnit_Framework_Error_Warning::$enabled = TRUE;
/* 		runq("DELETE FROM member_ids WHERE member_id='10000000';"); */


		if (Conf::$dblang == "mysql") {
			runq("DROP TABLE sequence_test_id_seq;");
		}

		runq("DROP TABLE sequence_test;");
	}

	public function testNextvalEmptyTable() {
		$nextval = db_nextval("sequence_test", "id");
		$this->assertNotEmpty($nextval);
		$this->assertEquals("1", $nextval, "sequence was not correctly incremented");
	}

	public function testNextvalNonEmptyTable() {
		runq("INSERT INTO sequence_test (id) VALUES (DEFAULT);");
		$nextval = db_nextval("sequence_test", "id");
		$this->assertNotEmpty($nextval);
		$this->assertEquals("2", $nextval, "sequence was not correctly incremented");
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

	public function testNextvalConcurrent() {
		$nextval = db_nextval("sequence_test", "id");
		$this->assertNotEmpty($nextval);
		$this->assertEquals("1", $nextval, "sequence was not correctly incremented");

		$nextval = db_nextval("sequence_test", "id");
		$this->assertNotEmpty($nextval);
		$this->assertNotEquals("1", $nextval, "sequence did not increment concurrently");
		$this->assertEquals("2", $nextval, "sequence was not correctly incremented");
	}
}

?>