<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_societies.php");

class MemberSocietiesAddTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberSocieties;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testadd_data() {
		$this->model->add_data(array("member_id" => "10000000", "society" => "TS01", "grade" => "STUD"));

		$member_query = runq("SELECT * FROM societies WHERE member_id='10000000';");
		$this->assertNotEmpty($member_query, "society was not created");
		$this->assertEquals("TS01", $member_query[0]['society'], "society was not set correctly");
		$this->assertEquals("STUD", $member_query[0]['grade'], "society was not set correctly");
	}
}

?>