<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_addresses.php");

class MemberAddressesAddTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberAddresses;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");
	}

	protected function tearDown() {
/* 		$this->user_model->delete_data("10000000"); */
		runq("DELETE FROM member_ids WHERE member_id='10000000';");
	}
	
	public function testadd_data() {
		$this->model->add_data(array("member_id" => "10000000", "type" => "PRIV", "address" => "123 fake st", "suburb" => "somewhere", "state" => "act", "postcode" => "1234", "country" => "AA"));

		$member_query = runq("SELECT * FROM addresses WHERE member_id='10000000';");
		$this->assertNotEmpty($member_query, "address was not created");
		$this->assertEquals("PRIV", $member_query[0]['type'], "address was not set correctly");
		$this->assertEquals("123 fake st", $member_query[0]['address'], "address was not set correctly");
		$this->assertEquals("somewhere", $member_query[0]['suburb'], "address was not set correctly");
		$this->assertEquals("act", $member_query[0]['state'], "address was not set correctly");
		$this->assertEquals("1234", $member_query[0]['postcode'], "address was not set correctly");
		$this->assertEquals("AA", $member_query[0]['country'], "address was not set correctly");
	}
}

?>