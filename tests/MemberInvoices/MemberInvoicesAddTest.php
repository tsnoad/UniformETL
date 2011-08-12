<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_invoices.php");

class MemberInvoicesAddTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberInvoices;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testadd_data() {
		$this->model->add_data(array("member_id" => "10000000", "batchid" => "101000000", "batchposition" => "1", "type" => "INV", "status" => "ACQU", "amount" => "3.14"));

		$member_query = runq("SELECT * FROM invoices WHERE member_id='10000000';");
		$this->assertNotEmpty($member_query, "invoice was not created");
		$this->assertEquals("101000000", $member_query[0]['batchid'], "invoice was not set correctly");
		$this->assertEquals("1", $member_query[0]['batchposition'], "invoice was not set correctly");
		$this->assertEquals("INV", $member_query[0]['type'], "invoice was not set correctly");
		$this->assertEquals("ACQU", $member_query[0]['status'], "invoice was not set correctly");
		$this->assertEquals("3.14", $member_query[0]['amount'], "invoice was not set correctly");
	}
}

?>