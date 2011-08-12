<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/transform/globaltiming.php");

class GlobalTimingTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->timing = new GlobalTiming;
	}

	protected function tearDown() {
	}

	public function teststart_timing() {
		$this->timing->start_timing();

		$this->assertNotEmpty($this->timing->start_time);
	}

	public function testchunk_started() {
		$this->timing->chunk_started();

		$this->assertNotEmpty($this->timing->chunk_start_time);
	}

	public function testchunk_completed() {
		$this->timing->chunk_started();

		$this->timing->chunk_completed();

		$this->assertNotEmpty($this->timing->chunk_durations);
		$this->assertEmpty($this->timing->chunk_start_time);
		$this->assertNotEmpty($this->timing->chunks_completed);
	}

	public function testeta_report() {
		$this->timing->chunk_count = 2;

		$this->timing->chunk_started();

		$this->timing->chunk_completed();

		ob_start();

		$this->timing->eta_report();

		$this->assertNotEmpty(ob_get_contents());

		ob_clean();
	}
}

?>