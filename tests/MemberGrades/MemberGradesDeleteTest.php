<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_grades.php");

class MemberGradesDeleteTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberGrades;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$this->model->add_data(array("member_id" => "10000000", "grade" => "Some Grade", "chartered" => "t"));
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testdelete_data() {
		$this->model->delete_data(array("member_id" => "10000000", "grade" => "Some Grade", "chartered" => "t"));

		$grade_query = runq("SELECT * FROM grades WHERE member_id='10000000';");
		$this->assertEmpty($grade_query, "grade was not deleted");
	}
}

?>