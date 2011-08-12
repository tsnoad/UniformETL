<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_statuses.php");

class MemberStatusesStatusesAddTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberStatuses;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testadd_data() {
		$this->model->add_data(array("member_id" => "10000000", "member" => "t", "financial" => "t"));

		$status_query = runq("SELECT * FROM statuses WHERE member_id='10000000';");
		$this->assertNotEmpty($status_query, "status was not created");
		if (Conf::$dblang == "pgsql") {
			$this->assertEquals("t", $status_query[0]['member'], "status was not set correctly");
			$this->assertEquals("t", $status_query[0]['financial'], "status was not set correctly");
		} else if (Conf::$dblang == "mysql") {
			$this->assertEquals("1", $status_query[0]['member'], "status was not set correctly");
			$this->assertEquals("1", $status_query[0]['financial'], "status was not set correctly");
		}
	}
}

?>