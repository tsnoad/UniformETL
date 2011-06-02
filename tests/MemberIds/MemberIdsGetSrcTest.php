<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");

class MemberIdsGetSrcTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberIds;

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
		runq("DELETE FROM dump_cpgcustomer WHERE customerid='10000000';");
		runq("DELETE FROM processes WHERE process_id='".pg_escape_string($this->process_id)."';");
	}
	
	public function testget_src_data() {
		$member_ids = $this->model->get_src_data($this->chunk_id);

		$this->assertNotEmpty($member_ids);
		$this->assertNotEmpty($member_ids['10000000']);
		$this->assertEquals("10000000", $member_ids['10000000']);
	}
}

?>