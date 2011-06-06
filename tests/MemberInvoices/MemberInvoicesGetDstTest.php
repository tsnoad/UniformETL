<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_invoices.php");

class MemberInvoicesGetDstTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberInvoices;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$process_id_query = runq("SELECT nextval('processes_process_id_seq');");
		$this->process_id = $process_id_query[0]['nextval'];
		runq("INSERT INTO processes (process_id) VALUES ('".pg_escape_string($this->process_id)."');");

		$chunk_id_query = runq("SELECT nextval('chunks_chunk_id_seq');");
		$this->chunk_id = $chunk_id_query[0]['nextval'];
		runq("INSERT INTO chunks (chunk_id, process_id) VALUES ('".pg_escape_string($this->chunk_id)."', '".pg_escape_string($this->process_id)."');");
		runq("INSERT INTO chunk_member_ids (chunk_id, member_id) VALUES ('".pg_escape_string($this->chunk_id)."', 10000000);");

		$this->model->add_data(array("member_id" => "10000000", "batch_hash" => md5("something1234"), "type" => "INV", "status" => "ACQU", "amount" => "3.14"));
	}

	protected function tearDown() {
/* 		$this->user_model->delete_data("10000000"); */
		runq("DELETE FROM member_ids WHERE member_id='10000000';");
		runq("DELETE FROM processes WHERE process_id='".pg_escape_string($this->process_id)."';");
	}
	
	public function testget_dst_data() {
		$member_invoices = $this->model->get_dst_data($this->chunk_id);

		$this->assertNotEmpty($member_invoices);
		$this->assertNotEmpty($member_invoices['10000000']);
		$this->assertNotEmpty($member_invoices['10000000'][md5("10000000".md5("something1234")."INV"."ACQU"."3.14")]);
		$this->assertEquals(md5("something1234"), $member_invoices['10000000'][md5("10000000".md5("something1234")."INV"."ACQU"."3.14")]['batch_hash']);
		$this->assertEquals("INV", $member_invoices['10000000'][md5("10000000".md5("something1234")."INV"."ACQU"."3.14")]['type']);
		$this->assertEquals("ACQU", $member_invoices['10000000'][md5("10000000".md5("something1234")."INV"."ACQU"."3.14")]['status']);
		$this->assertEquals("3.14", $member_invoices['10000000'][md5("10000000".md5("something1234")."INV"."ACQU"."3.14")]['amount']);
	}
}

?>