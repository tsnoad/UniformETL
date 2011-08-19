<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/transform/transform_launcher.php");

class TransformLauncherMultipleExtractsTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->launcher = new TransformLauncher;

		$this->sql_24_hours_ago = db_choose(db_pgsql("now() - INTERVAL '24 hours'"), db_mysql("date_sub(now(), INTERVAL 24 hour)"));
		$this->sql_22_hours_ago = db_choose(db_pgsql("now() - INTERVAL '22 hours'"), db_mysql("date_sub(now(), INTERVAL 22 hour)"));
		$this->sql_12_hours_ago = db_choose(db_pgsql("now() - INTERVAL '12 hours'"), db_mysql("date_sub(now(), INTERVAL 12 hour)"));
		$this->sql_11_hours_ago = db_choose(db_pgsql("now() - INTERVAL '11 hours'"), db_mysql("date_sub(now(), INTERVAL 11 hour)"));

		$this->old_extract_id = db_nextval("extract_processes", "extract_id");
		runq("INSERT INTO extract_processes (extract_id, start_date, finished, finish_date, failed, extractor, extract_pid) VALUES ('".db_escape($this->old_extract_id)."', {$this->sql_24_hours_ago}, TRUE, {$this->sql_22_hours_ago}, FALSE, 'full', 1);");

		$this->new_extract_id = db_nextval("extract_processes", "extract_id");
		runq("INSERT INTO extract_processes (extract_id, start_date, finished, finish_date, failed, extractor, extract_pid) VALUES ('".db_escape($this->new_extract_id)."', now(), TRUE, now(), FALSE, 'full', 1);");
	}

	protected function tearDown() {
		runq("DELETE FROM extract_processes WHERE extract_id='".db_escape($this->old_extract_id)."';");
		runq("DELETE FROM extract_processes WHERE extract_id='".db_escape($this->new_extract_id)."';");
	}


	//make sure we get the newest extract first
	public function testget_candidate_extracts() {
		$candidates = $this->launcher->get_candidate_extracts();

		$this->assertNotContains(array("extract_id" => $this->old_extract_id), (array)$candidates);
		$this->assertContains(array("extract_id" => $this->new_extract_id), (array)$candidates);

		//make sure the newer extract is first in the array
		$this->assertEquals(array("extract_id" => $this->new_extract_id), $candidates[0]);
	}


	public function testget_candidate_extractsOlderExtractHasFailed() {
		runq("UPDATE extract_processes SET failed=TRUE WHERE extract_id='".db_escape($this->old_extract_id)."';");

		$candidates = $this->launcher->get_candidate_extracts();

		$this->assertNotContains(array("extract_id" => $this->old_extract_id), (array)$candidates);
		$this->assertContains(array("extract_id" => $this->new_extract_id), (array)$candidates);
	}

	public function testget_candidate_extractsNewerExtractHasFailed() {
		runq("UPDATE extract_processes SET failed=TRUE WHERE extract_id='".db_escape($this->new_extract_id)."';");

		$candidates = $this->launcher->get_candidate_extracts();

		$this->assertNotContains(array("extract_id" => $this->old_extract_id), (array)$candidates);
		$this->assertNotContains(array("extract_id" => $this->new_extract_id), (array)$candidates);
	}

	public function testget_candidate_extractsOlderExtractHasBeenTransformed() {
		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->old_extract_id)."', {$this->sql_12_hours_ago}, TRUE, {$this->sql_11_hours_ago}, FALSE, 1);");

		$candidates = $this->launcher->get_candidate_extracts();

		$this->assertNotContains(array("extract_id" => $this->old_extract_id), (array)$candidates);
		$this->assertContains(array("extract_id" => $this->new_extract_id), (array)$candidates);
	}

	public function testget_candidate_extractsOlderExtractIsBeingTransformed() {
		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->old_extract_id)."', {$this->sql_12_hours_ago}, FALSE, NULL, FALSE, 1);");

		$candidates = $this->launcher->get_candidate_extracts();

		$this->assertNotContains(array("extract_id" => $this->old_extract_id), (array)$candidates);
		$this->assertContains(array("extract_id" => $this->new_extract_id), (array)$candidates);
	}

	public function testget_candidate_extractsOlderExtractHasBeenTransformedAndFailed() {
		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->old_extract_id)."', {$this->sql_12_hours_ago}, TRUE, {$this->sql_11_hours_ago}, TRUE, 1);");

		$candidates = $this->launcher->get_candidate_extracts();

		$this->assertNotContains(array("extract_id" => $this->old_extract_id), (array)$candidates);
		$this->assertContains(array("extract_id" => $this->new_extract_id), (array)$candidates);
	}

	public function testget_candidate_extractsNewerExtractHasBeenTransformed() {
		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->new_extract_id)."', now(), TRUE, now(), FALSE, 1);");

		$candidates = $this->launcher->get_candidate_extracts();

		$this->assertNotContains(array("extract_id" => $this->old_extract_id), (array)$candidates);
		$this->assertNotContains(array("extract_id" => $this->new_extract_id), (array)$candidates);
	}

	public function testget_candidate_extractsNewerExtractIsBeingTransformed() {
		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->new_extract_id)."', now(), FALSE, NULL, FALSE, 1);");

		$candidates = $this->launcher->get_candidate_extracts();

		$this->assertNotContains(array("extract_id" => $this->old_extract_id), (array)$candidates);
		$this->assertNotContains(array("extract_id" => $this->new_extract_id), (array)$candidates);
	}

	public function testget_candidate_extractsNewerExtractHasBeenTransformedAndFailed() {
		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->new_extract_id)."', now(), TRUE, now(), TRUE, 1);");

		$candidates = $this->launcher->get_candidate_extracts();

		$this->assertNotContains(array("extract_id" => $this->old_extract_id), (array)$candidates);
		$this->assertNotContains(array("extract_id" => $this->new_extract_id), (array)$candidates);
	}
}

?>