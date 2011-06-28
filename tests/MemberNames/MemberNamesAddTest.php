<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_names.php");

class MemberNamesAddTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberNames;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testadd_data() {
		$this->model->add_data(array("member_id" => "10000000", "type" => "PREF", "given_names" => "Some", "family_name" => "Name"));

		$member_query = runq("SELECT * FROM names WHERE member_id='10000000';");
		$this->assertNotEmpty($member_query, "name was not created");
		$this->assertEquals("PREF", $member_query[0]['type'], "name was not set correctly");
		$this->assertEquals("Some", $member_query[0]['given_names'], "name was not set correctly");
		$this->assertEquals("Name", $member_query[0]['family_name'], "name was not set correctly");
	}
}

?>