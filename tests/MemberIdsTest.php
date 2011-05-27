<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");

class MemberIdsTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberIds;
	}
	
	public function testget_src_data() {
		$this->assertTrue(method_exists($this->model, "get_src_data"), "get_src_data() does not exist");
	}
	
	public function testget_dst_data() {
		$this->assertTrue(method_exists($this->model, "get_dst_data"), "get_dst_data() does not exist");
	}
	
    /**
     * @dataProvider provider
     */
	public function testadd_data($member_id) {
		$this->assertTrue(method_exists($this->model, "add_data"), "add_data() does not exist");

		$this->model->add_data($member_id);

		$member_query = runq("SELECT * FROM member_ids WHERE member_id='".pg_escape_string($member_id)."';");

		$this->assertNotEmpty($member_query, "member was not created");
	}
	
    /**
     * @dataProvider provider
     */
	public function testupdate_data($member_id) {
		$this->assertTrue(method_exists($this->model, "update_data"), "update_data() does not exist");
	}
	
    /**
     * @dataProvider provider
     */
	public function testdelete_data($member_id) {
		$this->assertTrue(method_exists($this->model, "delete_data"), "delete_data() does not exist");

		$this->model->delete_data($member_id);

		$member_query = runq("SELECT * FROM member_ids WHERE member_id='".pg_escape_string($member_id)."';");

		$this->assertEmpty($member_query, "member was not deleted");

		runq("DELETE FROM member_ids WHERE member_id='".pg_escape_string($member_id)."';");
	}
	
	public function testtransform() {
		$this->assertTrue(method_exists($this->model, "transform"), "transform() does not exist");
	}

	public function provider() {
		return array(
			array("10000000")
		);
	}
}

?>