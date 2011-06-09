<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_receipts.php");

class MemberReceiptsAddTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberReceipts;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testadd_data() {
		$this->model->add_data(array("member_id" => "10000000", "batch_hash" => md5("something1234"), "type" => "SING", "status" => "PROC", "amount" => "3.14"));

		$member_query = runq("SELECT * FROM receipts WHERE member_id='10000000';");
		$this->assertNotEmpty($member_query, "receipt was not created");
		$this->assertEquals(md5("something1234"), $member_query[0]['batch_hash'], "receipt was not set correctly");
		$this->assertEquals("SING", $member_query[0]['type'], "receipt was not set correctly");
		$this->assertEquals("PROC", $member_query[0]['status'], "receipt was not set correctly");
		$this->assertEquals("3.14", $member_query[0]['amount'], "receipt was not set correctly");
	}
}

?>