<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_grades.php");

class MemberGradesUpdateTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberGrades;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$this->model->add_data(array("member_id" => "10000000", "grade" => "Some Grade", "chartered" => "t"));
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testupdate_data() {
		$this->model->update_data(array("member_id" => "10000000", "grade" => "Some Other Grade", "chartered" => "f"));

		$grade_query = runq("SELECT * FROM grades WHERE member_id='10000000';");
		$this->assertNotEmpty($grade_query, "grade was not updated");
		$this->assertEquals("Some Other Grade", $grade_query[0]['grade'], "grade was not set correctly");
		if (Conf::$dblang == "pgsql") {
			$this->assertEquals("f", $grade_query[0]['chartered'], "grade was not set correctly");
		} else if (Conf::$dblang == "mysql") {
			$this->assertEquals("0", $grade_query[0]['chartered'], "grade was not set correctly");
		}
	}
}

?>