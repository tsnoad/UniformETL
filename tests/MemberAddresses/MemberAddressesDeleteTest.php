<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_addresses.php");

class MemberAddressesDeleteTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberAddresses;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$this->model->add_data(array("member_id" => "10000000", "type" => "PRIV", "address" => "123 fake st", "suburb" => "somewhere", "state" => "act", "postcode" => "1234", "country" => "AA"));
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testdelete_data() {
		$this->model->delete_data(array(array("member_id" => "10000000", "type" => "PRIV", "address" => "123 fake st", "suburb" => "somewhere", "state" => "act", "postcode" => "1234", "country" => "AA")));

		$member_query = runq("SELECT * FROM addresses WHERE member_id='10000000';");
		$this->assertEmpty($member_query, "address was not deleted");
	}
}

?>