<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");

class MemberIdsTransformTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberIds;

		$this->model->add_data("10000000");
		runq("INSERT INTO dump_cpgcustomer (customerid, cpgid) VALUES ('10000000', 'IEA');");

		$process_id_query = runq("SELECT nextval('processes_process_id_seq');");
		$this->process_id = $process_id_query[0]['nextval'];
		runq("INSERT INTO processes (process_id) VALUES ('".pg_escape_string($this->process_id)."');");

		$chunk_id_query = runq("SELECT nextval('chunks_chunk_id_seq');");
		$this->chunk_id = $chunk_id_query[0]['nextval'];
		runq("INSERT INTO chunks (chunk_id, process_id) VALUES ('".pg_escape_string($this->chunk_id)."', '".pg_escape_string($this->process_id)."');");
		runq("INSERT INTO chunk_member_ids (chunk_id, member_id) VALUES ('".pg_escape_string($this->chunk_id)."', 10000000);");
	}

	protected function tearDown() {
/* 		$this->user_model->delete_data("10000000"); */
		runq("DELETE FROM member_ids WHERE member_id='10000000';");
		runq("DELETE FROM dump_cpgcustomer WHERE customerid='10000000';");
		runq("DELETE FROM processes WHERE process_id='".pg_escape_string($this->process_id)."';");
	}
	
	public function testtransform() {
		$src_members = $this->model->get_src_data($this->chunk_id);
		$dst_members = $this->model->get_dst_data($this->chunk_id);

		$return = $this->model->transform($src_members, $dst_members);
		$this->assertNotEmpty($return);

		list($members_add, $members_update, $members_delete, $data_delete_count) = $return;

		$this->assertEmpty($members_add);
		$this->assertEmpty($members_update);
		$this->assertEmpty($members_delete);
		$this->assertEmpty($data_delete_count);;
	}
}

?>