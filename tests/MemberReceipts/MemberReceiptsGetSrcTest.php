<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_receipts.php");

class MemberReceiptsGetSrcTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberReceipts;

		runq("INSERT INTO dump_receipt (customerid, batchid, batchposition, receipttypeid, receiptstatusid, amount) VALUES ('10000000', 'something', '1234', 'SING', 'PROC', '3.14');");

		$process_id_query = runq("SELECT nextval('processes_process_id_seq');");
		$this->process_id = $process_id_query[0]['nextval'];
		runq("INSERT INTO processes (process_id) VALUES ('".pg_escape_string($this->process_id)."');");

		$chunk_id_query = runq("SELECT nextval('chunks_chunk_id_seq');");
		$this->chunk_id = $chunk_id_query[0]['nextval'];
		runq("INSERT INTO chunks (chunk_id, process_id) VALUES ('".pg_escape_string($this->chunk_id)."', '".pg_escape_string($this->process_id)."');");
		runq("INSERT INTO chunk_member_ids (chunk_id, member_id) VALUES ('".pg_escape_string($this->chunk_id)."', 10000000);");
	}

	protected function tearDown() {
		runq("DELETE FROM dump_receipt WHERE customerid='10000000';");
		runq("DELETE FROM processes WHERE process_id='".pg_escape_string($this->process_id)."';");
	}
	
	public function testget_src_data() {
		$member_receipts = $this->model->get_src_data($this->chunk_id);

		$this->assertNotEmpty($member_receipts);
		$this->assertNotEmpty($member_receipts['10000000']);
		$this->assertNotEmpty($member_receipts['10000000'][md5("10000000".md5("something1234")."SING"."PROC"."3.14")]);
		$this->assertEquals(md5("something1234"), $member_receipts['10000000'][md5("10000000".md5("something1234")."SING"."PROC"."3.14")]['batch_hash']);
		$this->assertEquals("SING", $member_receipts['10000000'][md5("10000000".md5("something1234")."SING"."PROC"."3.14")]['type']);
		$this->assertEquals("PROC", $member_receipts['10000000'][md5("10000000".md5("something1234")."SING"."PROC"."3.14")]['status']);
		$this->assertEquals("3.14", $member_receipts['10000000'][md5("10000000".md5("something1234")."SING"."PROC"."3.14")]['amount']);
	}
}

?>