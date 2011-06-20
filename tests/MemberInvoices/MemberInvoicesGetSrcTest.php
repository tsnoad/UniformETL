<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_emails.php");
require_once("/etc/uniformetl/transform/transform_models/member_invoices.php");

class MemberInvoicesGetSrcTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberInvoices;

		runq("INSERT INTO dump_invoice (customerid, batchid, batchposition, invoicetypeid, invoicestatusid, amount) VALUES ('10000000', 'something', '1234', 'INV', 'ACQU', '3.14');");

		$extract_id_query = runq("SELECT nextval('extract_processes_extract_id_seq');");
		$this->extract_id = $extract_id_query[0]['nextval'];
		runq("INSERT INTO extract_processes (extract_id, start_date, finished, finish_date, failed, extractor, extract_pid) VALUES ('".pg_escape_string($this->extract_id)."', now(), TRUE, now(), FALSE, 'full', 1);");

		$transform_id_query = runq("SELECT nextval('transform_processes_transform_id_seq');");
		$this->transform_id = $transform_id_query[0]['nextval'];
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".pg_escape_string($this->transform_id)."', '".pg_escape_string($this->extract_id)."', now(), TRUE, now(), FALSE, 1);");

		$chunk_id_query = runq("SELECT nextval('chunks_chunk_id_seq');");
		$this->chunk_id = $chunk_id_query[0]['nextval'];
		runq("INSERT INTO chunks (chunk_id, transform_id) VALUES ('".pg_escape_string($this->chunk_id)."', '".pg_escape_string($this->transform_id)."');");
		runq("INSERT INTO chunk_member_ids (chunk_id, member_id) VALUES ('".pg_escape_string($this->chunk_id)."', 10000000);");
	}

	protected function tearDown() {
		runq("DELETE FROM dump_invoice WHERE customerid='10000000';");
		runq("DELETE FROM extract_processes WHERE extract_id='".pg_escape_string($this->extract_id)."';");
	}
	
	public function testget_src_data() {
		$member_invoices = $this->model->get_src_data($this->chunk_id);

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