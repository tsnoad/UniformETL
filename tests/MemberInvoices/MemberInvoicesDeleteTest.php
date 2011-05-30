<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_invoices.php");

class MemberInvoicesDeleteTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberInvoices;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$this->model->add_data(array("member_id" => "10000000", "batch_hash" => md5("something1234"), "type" => "INV", "status" => "ACQU", "amount" => "3.14"));
	}

	protected function tearDown() {
/* 		$this->user_model->delete_data("10000000"); */
		runq("DELETE FROM member_ids WHERE member_id='10000000';");
	}
	
	public function testdelete_data() {
		$this->model->delete_data(array(array("member_id" => "10000000", "batch_hash" => md5("something1234"), "type" => "INV", "status" => "ACQU", "amount" => "3.14")));

		$member_query = runq("SELECT * FROM invoices WHERE member_id='10000000';");
		$this->assertEmpty($member_query, "invoice was not deleted");
	}
}

?>