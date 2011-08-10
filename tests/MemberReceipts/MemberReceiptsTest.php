<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/transform/transform_models/member_receipts.php");

class MemberReceiptsTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberReceipts;
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
	
	public function testRequiredTransforms() {
		$required = $this->model->hook_models_required_transforms("");

		$this->assertTrue(is_array($required));
	}
	
	public function testRequiredTables() {
		$required = $this->model->hook_models_required_tables("");

		$this->assertTrue(is_array($required));
		$this->assertNotEmpty(reset($required));
		$this->assertNotEmpty(reset(array_keys($required)));
		$this->assertContains("%{extract_id}", reset(array_keys($required)));
	}
	
	public function testPriority() {
		$priority = $this->model->hook_models_transform_priority("");

		$this->assertContains($priority, array("primary", "secondary", "tertiary"), "priority is not acceptable");
	}
	
	public function testIndexes() {
		$indexes = $this->model->hook_extract_index_sql("");

		$this->assertTrue(is_array($indexes));
	}
}

?>