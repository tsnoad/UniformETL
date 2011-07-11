<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_colleges.php");

class MemberCollegesAddTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberColleges;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testadd_data() {
		$this->model->add_data(array("member_id" => "10000000", "college" => "COLL", "grade" => "GRAD"));

		$member_query = runq("SELECT * FROM colleges WHERE member_id='10000000';");
		$this->assertNotEmpty($member_query, "college was not created");
		$this->assertEquals("COLL", $member_query[0]['college'], "college was not set correctly");
		$this->assertEquals("GRAD", $member_query[0]['grade'], "college was not set correctly");
	}
}

?>