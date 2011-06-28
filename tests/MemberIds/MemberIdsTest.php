<?php

require_once("/etc/uniformetl/config.php");
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

	public function testadd_data() {
		$this->assertTrue(method_exists($this->model, "add_data"), "add_data() does not exist");
	}
	
	public function testupdate_data() {
		$this->assertTrue(method_exists($this->model, "update_data"), "update_data() does not exist");
	}

	public function testdelete_data() {
		$this->assertTrue(method_exists($this->model, "delete_data"), "delete_data() does not exist");
	}
	
	public function testtransform() {
		$this->assertTrue(method_exists($this->model, "transform"), "transform() does not exist");
	}
}

?>