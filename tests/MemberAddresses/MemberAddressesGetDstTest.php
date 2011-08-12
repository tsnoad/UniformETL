<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_addresses.php");

class MemberAddressesGetDstTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberAddresses;

		$this->user_model = new MemberIds;

		$this->user_model->add_data("10000000");

		$this->extract_id = db_nextval("extract_processes", "extract_id");
		runq("INSERT INTO extract_processes (extract_id, start_date, finished, finish_date, failed, extractor, extract_pid) VALUES ('".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 'full', 1);");

		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 1);");

		$this->chunk_id = db_nextval("chunks", "chunk_id");
		runq("INSERT INTO chunks (chunk_id, transform_id) VALUES ('".db_escape($this->chunk_id)."', '".db_escape($this->transform_id)."');");
		runq("INSERT INTO chunk_member_ids (chunk_id, member_id) VALUES ('".db_escape($this->chunk_id)."', 10000000);");

		$this->model->add_data(array("member_id" => "10000000", "type" => "PRIV", "address" => "123 fake st", "suburb" => "somewhere", "state" => "act", "postcode" => "1234", "country" => "AA"));
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
		runq("DELETE FROM extract_processes WHERE extract_id='".db_escape($this->extract_id)."';");
	}
	
	public function testget_dst_data() {
		$member_addresses = $this->model->get_dst_data($this->chunk_id);

		$this->assertNotEmpty($member_addresses);
		$this->assertNotEmpty($member_addresses['10000000']);
		$this->assertNotEmpty($member_addresses['10000000'][md5("10000000"."PRIV"."123 fake st"."somewhere"."act"."1234"."AA")]);
		$this->assertEquals("PRIV", $member_addresses['10000000'][md5("10000000"."PRIV"."123 fake st"."somewhere"."act"."1234"."AA")]['type']);
		$this->assertEquals("123 fake st", $member_addresses['10000000'][md5("10000000"."PRIV"."123 fake st"."somewhere"."act"."1234"."AA")]['address']);
		$this->assertEquals("somewhere", $member_addresses['10000000'][md5("10000000"."PRIV"."123 fake st"."somewhere"."act"."1234"."AA")]['suburb']);
		$this->assertEquals("act", $member_addresses['10000000'][md5("10000000"."PRIV"."123 fake st"."somewhere"."act"."1234"."AA")]['state']);
		$this->assertEquals("1234", $member_addresses['10000000'][md5("10000000"."PRIV"."123 fake st"."somewhere"."act"."1234"."AA")]['postcode']);
		$this->assertEquals("AA", $member_addresses['10000000'][md5("10000000"."PRIV"."123 fake st"."somewhere"."act"."1234"."AA")]['country']);
	}
}

?>