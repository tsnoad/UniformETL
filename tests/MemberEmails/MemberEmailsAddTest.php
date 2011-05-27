<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_emails.php");

class MemberEmailsAddTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberEmails;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");
	}

	protected function tearDown() {
/* 		$this->user_model->delete_data("10000000"); */
		runq("DELETE FROM member_ids WHERE member_id='10000000';");
	}
	
	public function testadd_data() {
		$this->model->add_data(array("member_id" => "10000000", "email" => "someone@example.com"));

		$member_query = runq("SELECT * FROM emails WHERE member_id='10000000';");
		$this->assertNotEmpty($member_query, "email was not created");
		$this->assertEquals("someone@example.com", $member_query[0]['email'], "email was not set correctly");
	}
}

?>