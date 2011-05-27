<?php

require_once("/etc/uniformetl/transform/transform_models/member_ids.php");

class MemberIdsTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberIds;
	}
	
	public function testget_src_data() {
		$this->assertTrue(method_exists($this->model, "get_src_data"));
	}
	
	public function testget_dst_data() {
		$this->assertTrue(method_exists($this->model, "get_dst_data"));
	}
	
	public function testadd_data() {
		$this->assertTrue(method_exists($this->model, "add_data"));
	}
	
	public function testupdate_data() {
		$this->assertTrue(method_exists($this->model, "update_data"));
	}
	
	public function testdelete_data() {
		$this->assertTrue(method_exists($this->model, "delete_data"));
	}
	
	public function testtransform() {
		$this->assertTrue(method_exists($this->model, "transform"));
	}
}

?>