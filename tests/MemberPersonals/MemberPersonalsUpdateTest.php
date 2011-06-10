<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_personals.php");

class MemberPersonalsUpdateTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberPersonals;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$this->model->add_data(array("member_id" => "10000000", "gender" => "M", "date_of_birth" => "2011-06-10 13:24:00"));
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testupdate_data() {
		$this->model->update_data(array("member_id" => "10000000", "gender" => "F", "date_of_birth" => "1966-06-10 13:24:00"));

		$personal_query = runq("SELECT * FROM personals WHERE member_id='10000000';");
		$this->assertNotEmpty($personal_query, "personal was not created");
		$this->assertEquals("F", $personal_query[0]['gender'], "personal was not set correctly");
		$this->assertEquals("1966-06-10 13:24:00", $personal_query[0]['date_of_birth'], "personal was not set correctly");
	}
	
	public function testupdate_data_null() {
		$this->model->update_data(array("member_id" => "10000000", "gender" => "", "date_of_birth" => ""));

		$personal_query = runq("SELECT * FROM personals WHERE member_id='10000000';");
		$this->assertNotEmpty($personal_query, "personal was not created");
		$this->assertNull($personal_query[0]['gender'], "personal was not set correctly");
		$this->assertNull($personal_query[0]['date_of_birth'], "personal was not set correctly");
	}
}

?>