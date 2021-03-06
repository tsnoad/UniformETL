<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_colleges.php");

class MemberCollegesGetSrcTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberColleges;

		$this->extract_id = db_nextval("extract_processes", "extract_id");
		runq("INSERT INTO extract_processes (extract_id, start_date, finished, finish_date, failed, extractor, extract_pid) VALUES ('".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 'full', 1);");

		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 1);");

		$this->chunk_id = db_nextval("chunks", "chunk_id");
		runq("INSERT INTO chunks (chunk_id, transform_id) VALUES ('".db_escape($this->chunk_id)."', '".db_escape($this->transform_id)."');");
		runq("INSERT INTO chunk_member_ids (chunk_id, member_id) VALUES ('".db_escape($this->chunk_id)."', 10000000);");

		runq("CREATE TABLE dump_{$this->extract_id}_cpggradetype (gradetypeid TEXT, classid TEXT);");
		runq("INSERT INTO dump_{$this->extract_id}_cpggradetype (gradetypeid, classid) VALUES ('FBAR', 'COLL');");

		runq("CREATE TABLE dump_{$this->extract_id}_gradehistory (customerid TEXT, cpgid TEXT, gradetypeid TEXT, gradeid TEXT, datechange TEXT, changereasonid TEXT);");
		runq("INSERT INTO dump_{$this->extract_id}_gradehistory (customerid, cpgid, gradetypeid, gradeid, datechange, changereasonid) VALUES ('10000000', 'IEA', 'FBAR', 'FELL', '', '');");
	}

	protected function tearDown() {
		runq("DROP TABLE dump_{$this->extract_id}_cpggradetype;");
		runq("DROP TABLE dump_{$this->extract_id}_gradehistory;");
		runq("DELETE FROM extract_processes WHERE extract_id='".db_escape($this->extract_id)."';");
	}
	
	public function testget_src_data() {
		$member_colleges = $this->model->get_src_data($this->chunk_id, $this->extract_id);

		$data_hash = md5("10000000"."FBAR"."FELL");

		$this->assertNotEmpty($member_colleges);
		$this->assertNotEmpty($member_colleges['10000000']);
		$this->assertNotEmpty($member_colleges['10000000'][$data_hash]);
		$this->assertEquals("FBAR", $member_colleges['10000000'][$data_hash]['college']);
		$this->assertEquals("FELL", $member_colleges['10000000'][$data_hash]['grade']);
	}
}

?>