<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_epdp_statuses.php");

class MemberEpdpStatusesGetSrcTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberEpdpStatuses;

		$this->extract_id = db_nextval("extract_processes", "extract_id");
		runq("INSERT INTO extract_processes (extract_id, start_date, finished, finish_date, failed, extractor, extract_pid) VALUES ('".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 'full', 1);");

		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 1);");

		$this->chunk_id = db_nextval("chunks", "chunk_id");
		runq("INSERT INTO chunks (chunk_id, transform_id) VALUES ('".db_escape($this->chunk_id)."', '".db_escape($this->transform_id)."');");
		runq("INSERT INTO chunk_member_ids (chunk_id, member_id) VALUES ('".db_escape($this->chunk_id)."', 10000000);");

		runq("CREATE TABLE dump_{$this->extract_id}_groupmember (customerid TEXT, groupid TEXT, subgroupid TEXT);");
		runq("INSERT INTO dump_{$this->extract_id}_groupmember (customerid, groupid, subgroupid) VALUES ('10000000', '6052', '28507');");
	}

	protected function tearDown() {
		runq("DROP TABLE dump_{$this->extract_id}_groupmember;");
		runq("DELETE FROM extract_processes WHERE extract_id='".db_escape($this->extract_id)."';");
	}
	
	public function testget_src_data() {
		$member_statuses = $this->model->get_src_data($this->chunk_id, $this->extract_id);

		$this->assertNotEmpty($member_statuses);
		$this->assertNotEmpty($member_statuses['10000000']);
		$this->assertEquals("10000000", $member_statuses['10000000']['member_id']);
		if (Conf::$dblang == "pgsql") {
			$this->assertEquals("t", $member_statuses['10000000']['participant']);
			$this->assertEquals("t", $member_statuses['10000000']['coordinator']);
		} else if (Conf::$dblang == "mysql") {
			$this->assertEquals("1", $member_statuses['10000000']['participant']);
			$this->assertEquals("1", $member_statuses['10000000']['coordinator']);
		}
	}
}

?>