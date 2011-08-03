<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_statuses.php");

class MemberStatusesGetDstTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberStatuses;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$this->extract_id = db_nextval("extract_processes", "extract_id");
		runq("INSERT INTO extract_processes (extract_id, start_date, finished, finish_date, failed, extractor, extract_pid) VALUES ('".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 'full', 1);");

		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 1);");

		$this->chunk_id = db_nextval("chunks", "chunk_id");
		runq("INSERT INTO chunks (chunk_id, transform_id) VALUES ('".db_escape($this->chunk_id)."', '".db_escape($this->transform_id)."');");
		runq("INSERT INTO chunk_member_ids (chunk_id, member_id) VALUES ('".db_escape($this->chunk_id)."', 10000000);");

		$this->model->add_data(array("member_id" => "10000000", "member" => "t", "financial" => "t"));
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
		runq("DELETE FROM extract_processes WHERE extract_id='".db_escape($this->extract_id)."';");
	}
	
	public function testget_dst_data() {
		$member_statuses = $this->model->get_dst_data($this->chunk_id);

		$this->assertNotEmpty($member_statuses);
		$this->assertNotEmpty($member_statuses['10000000']);
		$this->assertEquals("10000000", $member_statuses['10000000']['member_id']);
		if (Conf::$dblang == "pgsql") {
			$this->assertEquals("t", $member_statuses['10000000']['member']);
			$this->assertEquals("t", $member_statuses['10000000']['financial']);
		} else if (Conf::$dblang == "mysql") {
			$this->assertEquals("1", $member_statuses['10000000']['member']);
			$this->assertEquals("1", $member_statuses['10000000']['financial']);
		}
	}
}

?>