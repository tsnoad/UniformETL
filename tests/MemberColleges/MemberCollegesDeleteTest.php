<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_colleges.php");

class MemberCollegesDeleteTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberColleges;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$this->model->add_data(array("member_id" => "10000000", "college" => "FBAR", "grade" => "FELL"));
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testdelete_data() {
		$this->model->delete_data(array(array("member_id" => "10000000", "college" => "FBAR", "grade" => "FELL")));

		$member_query = runq("SELECT * FROM colleges WHERE member_id='10000000';");
		$this->assertEmpty($member_query, "college was not deleted");
	}
}

?>