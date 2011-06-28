<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_ecpd_statuses.php");

class MemberEcpdStatusesDeleteTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberEcpdStatuses;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$this->model->add_data("10000000");
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testdelete_data() {
		$this->model->delete_data("10000000");

		$status_query = runq("SELECT * FROM ecpd_statuses WHERE member_id='10000000';");
		$this->assertEmpty($status_query, "ecpd status was not deleted");
	}
}

?>