<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_statuses.php");

class MemberStatusesDeleteTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberStatuses;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$this->model->add_data(array("member_id" => "10000000", "member" => "t", "financial" => "t"));
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}
	
	public function testdelete_data() {
		$this->model->delete_data(array("member_id" => "10000000", "member" => "t", "financial" => "t"));

		$status_query = runq("SELECT * FROM statuses WHERE member_id='10000000';");
		$this->assertEmpty($status_query, "status was not deleted");
	}
}

?>