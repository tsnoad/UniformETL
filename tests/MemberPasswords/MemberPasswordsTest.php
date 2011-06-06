<?php

require_once("/etc/uniformetl/transform/transform_models/member_passwords.php");

class MemberPasswordsTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberPasswords;
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
	
	public function testupdate_or_add_data() {
		$this->assertTrue(method_exists($this->model, "update_or_add_data"), "update_or_add_data() does not exist");
	}
	
	public function testmake_salt() {
		$this->assertTrue(method_exists($this->model, "make_salt"), "make_salt() does not exist");

		$salt = $this->model->make_salt();
		$this->assertNotEmpty($salt, "make_salt() didn't return anything");
		$this->assertInternalType("string", $salt, "make_salt() didn't return a string");
		$this->assertRegExp("/^[a-zA-Z0-9]{32,32}$/", $salt, "make_salt() returned something unexpected");
	}
}

?>