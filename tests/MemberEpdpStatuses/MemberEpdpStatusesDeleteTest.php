<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_epdp_statuses.php");

class MemberEpdpStatusesDeleteTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberEpdpStatuses;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$this->model->add_data(array("member_id" => "10000000", "participant" => "t", "coordinator" => "t"));
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testdelete_data() {
		$this->model->delete_data(array("member_id" => "10000000", "participant" => "t", "coordinator" => "t"));

		$status_query = runq("SELECT * FROM epdp_statuses WHERE member_id='10000000';");
		$this->assertEmpty($status_query, "status was not deleted");
	}
}

?>