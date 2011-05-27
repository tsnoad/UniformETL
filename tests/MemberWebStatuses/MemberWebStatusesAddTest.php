<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_web_statuses.php");

class MemberWebStatusesAddTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberWebStatuses;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");
	}

	protected function tearDown() {
/* 		$this->user_model->delete_data("10000000"); */
		runq("DELETE FROM member_ids WHERE member_id='10000000';");
	}
	
	public function testadd_data() {
		$this->model->add_data("10000000");

		$status_query = runq("SELECT * FROM web_statuses WHERE member_id='10000000';");
		$this->assertNotEmpty($status_query, "web status was not created");
	}
}

?>