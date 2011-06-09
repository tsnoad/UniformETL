<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_names.php");

class MemberNamesDeleteTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberNames;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$this->model->add_data(array("member_id" => "10000000", "type" => "PREF", "given_names" => "Some", "family_name" => "Name"));
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testdelete_data() {
		$this->model->delete_data(array(array("member_id" => "10000000", "type" => "PREF", "given_names" => "Some", "family_name" => "Name")));

		$member_query = runq("SELECT * FROM names WHERE member_id='10000000';");
		$this->assertEmpty($member_query, "name was not deleted");
	}
}

?>