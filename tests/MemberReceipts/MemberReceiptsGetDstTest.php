<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_receipts.php");

class MemberReceiptsGetDstTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberReceipts;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$this->extract_id = db_nextval("extract_processes", "extract_id");
		runq("INSERT INTO extract_processes (extract_id, start_date, finished, finish_date, failed, extractor, extract_pid) VALUES ('".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 'full', 1);");

		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 1);");

		$this->chunk_id = db_nextval("chunks", "chunk_id");
		runq("INSERT INTO chunks (chunk_id, transform_id) VALUES ('".db_escape($this->chunk_id)."', '".db_escape($this->transform_id)."');");
		runq("INSERT INTO chunk_member_ids (chunk_id, member_id) VALUES ('".db_escape($this->chunk_id)."', 10000000);");

		$this->model->add_data(array("member_id" => "10000000", "batchid" => "101000000", "batchposition" => "1", "type" => "SING", "status" => "PROC", "amount" => "3.14"));
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
		runq("DELETE FROM extract_processes WHERE extract_id='".db_escape($this->extract_id)."';");
	}
	
	public function testget_dst_data() {
		$member_receipts = $this->model->get_dst_data($this->chunk_id);

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