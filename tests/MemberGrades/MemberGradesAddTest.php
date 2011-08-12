<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_grades.php");

class MemberGradesAddTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberGrades;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testadd_data() {
		$this->model->add_data(array("member_id" => "10000000", "grade" => "Some Grade", "chartered" => "t"));

		$member_query = runq("SELECT * FROM grades WHERE member_id='10000000';");
		$this->assertNotEmpty($member_query, "grade was not created");
		$this->assertEquals("Some Grade", $member_query[0]['grade'], "grade was not set correctly");
		$this->assertEquals("t", $member_query[0]['chartered'], "grade was not set correctly");
	}
}

?>