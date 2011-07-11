<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_colleges.php");

class MemberCollegesGetDstTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberColleges;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

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

		$this->model->add_data(array("member_id" => "10000000", "college" => "COLL", "grade" => "GRAD"));
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
		runq("DELETE FROM extract_processes WHERE extract_id='".pg_escape_string($this->extract_id)."';");
	}
	
	public function testget_dst_data() {
		$member_colleges = $this->model->get_dst_data($this->chunk_id);

		$data_hash = md5("10000000"."COLL"."GRAD");

		$this->assertNotEmpty($member_colleges);
		$this->assertNotEmpty($member_colleges['10000000']);
		$this->assertNotEmpty($member_colleges['10000000'][$data_hash]);
		$this->assertEquals("COLL", $member_colleges['10000000'][$data_hash]['college']);
		$this->assertEquals("GRAD", $member_colleges['10000000'][$data_hash]['grade']);
	}
}

?>