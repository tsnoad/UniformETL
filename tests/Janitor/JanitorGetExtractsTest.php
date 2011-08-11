<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/janitor/janitor.php");

class JanitorGetExtractsTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->janitor = new Janitor;

		$this->extract_id = db_nextval("extract_processes", "extract_id");
		runq("INSERT INTO extract_processes (extract_id, start_date, finished, finish_date, failed, extractor, extract_pid) VALUES ('".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 'full', 1);");
	}

	protected function tearDown() {
		runq("DELETE FROM extract_processes WHERE extract_id='".db_escape($this->extract_id)."';");
	}

	public function testSuccess() {
		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 1);");

		$extract_ids = $this->janitor->get_finished_extracts();

		$this->assertTrue(is_array($extract_ids));
		$this->assertContains($this->extract_id, $extract_ids);
	}

	public function testOneComplete() {
		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->extract_id)."', now(), FALSE, now(), FALSE, 1);");

		$extract_ids = $this->janitor->get_finished_extracts();

		$this->assertTrue(is_array($extract_ids));
		$this->assertNotContains($this->extract_id, $extract_ids);
	}

	public function testOneCompleteOneIncomplete() {
		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 1);");

		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->extract_id)."', now(), FALSE, now(), FALSE, 1);");

		$extract_ids = $this->janitor->get_finished_extracts();

		$this->assertTrue(is_array($extract_ids));
		$this->assertNotContains($this->extract_id, $extract_ids);
	}
}

?>