<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_societies.php");

class MemberSocietiesGetSrcTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberSocieties;

		$this->extract_id = db_nextval("extract_processes", "extract_id");
		runq("INSERT INTO extract_processes (extract_id, start_date, finished, finish_date, failed, extractor, extract_pid) VALUES ('".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 'full', 1);");

		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 1);");

		$this->chunk_id = db_nextval("chunks", "chunk_id");
		runq("INSERT INTO chunks (chunk_id, transform_id) VALUES ('".db_escape($this->chunk_id)."', '".db_escape($this->transform_id)."');");
		runq("INSERT INTO chunk_member_ids (chunk_id, member_id) VALUES ('".db_escape($this->chunk_id)."', 10000000);");

		runq("CREATE TABLE dump_{$this->extract_id}_cpgcustomer (customerid TEXT, cpgid TEXT, custstatusid TEXT, gradeid TEXT);");
		runq("INSERT INTO dump_{$this->extract_id}_cpgcustomer (customerid, cpgid, custstatusid, gradeid) VALUES ('10000000', 'TS01', 'MEMB', 'STUD');");
	}

	protected function tearDown() {
		runq("DROP TABLE dump_{$this->extract_id}_cpgcustomer;");
		runq("DELETE FROM extract_processes WHERE extract_id='".db_escape($this->extract_id)."';");
	}
	
	public function testget_src_data() {
		$member_societies = $this->model->get_src_data($this->chunk_id, $this->extract_id);

		$data_hash = md5("10000000"."TS01"."STUD");

		$this->assertNotEmpty($member_societies);
		$this->assertNotEmpty($member_societies['10000000']);
		$this->assertNotEmpty($member_societies['10000000'][$data_hash]);
		$this->assertEquals("TS01", $member_societies['10000000'][$data_hash]['society']);
		$this->assertEquals("STUD", $member_societies['10000000'][$data_hash]['grade']);
	}
}

?>