<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_epdp_statuses.php");

class MemberEpdpStatusesStatusesAddTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberEpdpStatuses;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testadd_data() {
		$this->model->add_data(array("member_id" => "10000000", "participant" => "t", "coordinator" => "t"));

		$status_query = runq("SELECT * FROM epdp_statuses WHERE member_id='10000000';");
		$this->assertNotEmpty($status_query, "epdp status was not created");
		if (Conf::$dblang == "pgsql") {
			$this->assertEquals("t", $status_query[0]['participant'], "status was not set correctly");
			$this->assertEquals("t", $status_query[0]['coordinator'], "status was not set correctly");
		} else if (Conf::$dblang == "mysql") {
			$this->assertEquals("1", $status_query[0]['participant'], "status was not set correctly");
			$this->assertEquals("1", $status_query[0]['coordinator'], "status was not set correctly");
		}
	}
}

?>