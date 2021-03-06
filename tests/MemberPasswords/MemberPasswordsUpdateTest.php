<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_passwords.php");

class MemberPasswordsUpdateTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberPasswords;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$this->model->add_data(array("member_id" => "10000000", "password" => "foobar123"));
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testupdate_data() {
		$this->model->update_data(array("member_id" => "10000000", "password" => "somethingdifferent"));

		$password_query = runq("SELECT * FROM passwords WHERE member_id='10000000';");
		$this->assertNotEmpty($password_query, "password was not updated");
		$this->assertEquals(md5($password_query[0]['salt']."somethingdifferent"), $password_query[0]['hash'], "password was not set correctly");
	}
}

?>