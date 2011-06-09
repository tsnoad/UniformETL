<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");

class MemberIdsDeleteTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberIds;

		$this->model->add_data("10000000");
	}

	protected function tearDown() {
		$this->model->delete_data("10000000");
	}
	
	public function testdelete_data() {
		$this->model->delete_data("10000000");

		$member_id_query = runq("SELECT * FROM member_ids WHERE member_id='10000000';");
		$this->assertEmpty($member_id_query, "member was not deleted");
	}
}

?>