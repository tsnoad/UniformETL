<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/transform/transform_launcher.php");

class TransformLauncherTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->launcher = new TransformLauncher;

		$this->extract_id = db_nextval("extract_processes", "extract_id");
		runq("INSERT INTO extract_processes (extract_id, start_date, finished, finish_date, failed, extractor, extract_pid) VALUES ('".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 'full', 1);");
	}

	protected function tearDown() {
		runq("DELETE FROM extract_processes WHERE extract_id='".db_escape($this->extract_id)."';");
	}

	public function testget_candidate_extracts() {
		$candidates = $this->launcher->get_candidate_extracts();

		$this->assertContains(array("extract_id" => $this->extract_id), (array)$candidates);
	}


	public function testget_candidate_extractsFailedExtract() {
		runq("UPDATE extract_processes SET failed=TRUE WHERE extract_id='".db_escape($this->extract_id)."';");

		$candidates = $this->launcher->get_candidate_extracts();

		$this->assertNotContains(array("extract_id" => $this->extract_id), (array)$candidates);
	}

	public function testget_candidate_extractsCurrentlyTransforming() {
		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->extract_id)."', now(), FALSE, NULL, FALSE, 1);");

		$candidates = $this->launcher->get_candidate_extracts();

		$this->assertNotContains(array("extract_id" => $this->extract_id), (array)$candidates);
	}

	public function testget_candidate_extractsAlreadyTransformed() {
		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 1);");

		$candidates = $this->launcher->get_candidate_extracts();

		$this->assertNotContains(array("extract_id" => $this->extract_id), (array)$candidates);
	}
}

?>