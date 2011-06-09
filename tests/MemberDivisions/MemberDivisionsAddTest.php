<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_divisions.php");

class MemberDivisionsAddTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberDivisions;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testadd_data() {
		$this->model->add_data(array("member_id" => "10000000", "division" => "Some Division"));

		$member_query = runq("SELECT * FROM divisions WHERE member_id='10000000';");
		$this->assertNotEmpty($member_query, "divisions was not created");
		$this->assertEquals("Some Division", $member_query[0]['division'], "division was not set correctly");
	}
}

?>