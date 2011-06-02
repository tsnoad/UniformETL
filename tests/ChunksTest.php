<?php

require_once("/etc/uniformetl/transform/chunks.php");

class ChunksTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->timing = new Chunks;
	}

	protected function tearDown() {
	}

	public function testSomething() {
	}
}

?>