<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");

class MemberIdsTransformTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberIds;

		$this->model->add_data("10000000");

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

		runq("CREATE TABLE dump_{$this->extract_id}_customer (customerid TEXT);");
		runq("INSERT INTO dump_{$this->extract_id}_customer (customerid) VALUES ('10000000');");
	}

	protected function tearDown() {
		$this->model->delete_data("10000000");
		runq("DROP TABLE dump_{$this->extract_id}_customer;");
		runq("DELETE FROM extract_processes WHERE extract_id='".pg_escape_string($this->extract_id)."';");
	}
	
	public function testtransform() {
		$src_members = $this->model->get_src_data($this->chunk_id, $this->extract_id);
		$dst_members = $this->model->get_dst_data($this->chunk_id);

		$return = $this->model->transform($src_members, $dst_members);
		$this->assertNotEmpty($return);

		list($data_add, $data_nochange, $data_update, $data_delete, $data_delete_count) = $return;

		$this->assertEmpty($members_add);

		$this->assertNotEmpty($data_nochange);
		$this->assertEquals("10000000", $data_nochange[0]);

		$this->assertEmpty($members_update);
		$this->assertEmpty($members_delete);
		$this->assertEmpty($data_delete_count);
	}
}

?>