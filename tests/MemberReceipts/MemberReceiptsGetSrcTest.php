<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_receipts.php");

class MemberReceiptsGetSrcTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberReceipts;

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

		runq("CREATE TABLE dump_{$this->extract_id}_receipt (customerid TEXT, batchid TEXT, batchposition TEXT, receipttypeid TEXT, receiptstatusid TEXT, amount TEXT, datereceived TEXT);");
		runq("INSERT INTO dump_{$this->extract_id}_receipt (customerid, batchid, batchposition, receipttypeid, receiptstatusid, amount, datereceived) VALUES ('10000000', '101000000', '1', 'SING', 'PROC', '3.14', '".date("M j Y g:i:s:000A", strtotime("-1 day"))."');");
	}

	protected function tearDown() {
		runq("DROP TABLE dump_{$this->extract_id}_receipt;");
		runq("DELETE FROM extract_processes WHERE extract_id='".pg_escape_string($this->extract_id)."';");
	}
	
	public function testget_src_data() {
		$member_receipts = $this->model->get_src_data($this->chunk_id, $this->extract_id);

		$data_hash = md5("10000000"."101000000"."1"."SING"."PROC"."3.14");

		$this->assertNotEmpty($member_receipts);
		$this->assertNotEmpty($member_receipts['10000000']);
		$this->assertNotEmpty($member_receipts['10000000'][$data_hash]);
		$this->assertEquals("101000000", $member_receipts['10000000'][$data_hash]['batchid']);
		$this->assertEquals("1", $member_receipts['10000000'][$data_hash]['batchposition']);
		$this->assertEquals("SING", $member_receipts['10000000'][$data_hash]['type']);
		$this->assertEquals("PROC", $member_receipts['10000000'][$data_hash]['status']);
		$this->assertEquals("3.14", $member_receipts['10000000'][$data_hash]['amount']);
	}
}

?>