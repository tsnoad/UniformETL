<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_addresses.php");

class MemberAddressesGetSrcTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberAddresses;

		$this->extract_id = db_nextval("extract_processes", "extract_id");
		runq("INSERT INTO extract_processes (extract_id, start_date, finished, finish_date, failed, extractor, extract_pid) VALUES ('".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 'full', 1);");

		$this->transform_id = db_nextval("transform_processes", "transform_id");
		runq("INSERT INTO transform_processes (transform_id, extract_id, start_date, finished, finish_date, failed, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->extract_id)."', now(), TRUE, now(), FALSE, 1);");

		$this->chunk_id = db_nextval("chunks", "chunk_id");
		runq("INSERT INTO chunks (chunk_id, transform_id) VALUES ('".db_escape($this->chunk_id)."', '".db_escape($this->transform_id)."');");
		runq("INSERT INTO chunk_member_ids (chunk_id, member_id) VALUES ('".db_escape($this->chunk_id)."', 10000000);");

		runq("CREATE TABLE dump_{$this->extract_id}_address (customerid TEXT, addrtypeid TEXT, line1 TEXT, line2 TEXT, line3 TEXT, suburb TEXT, state TEXT, postcode TEXT, countryid TEXT, valid TEXT);");
		runq("INSERT INTO dump_{$this->extract_id}_address (customerid, addrtypeid, line1, line2, line3, suburb, state, postcode, countryid, valid) VALUES ('10000000', 'PRIV', '123 fake st', 'tralalala', 'trolololol', 'somewhere', 'act', '1234', 'AA', '1');");
	}

	protected function tearDown() {
		runq("DROP TABLE dump_{$this->extract_id}_address;");
		runq("DELETE FROM extract_processes WHERE extract_id='".db_escape($this->extract_id)."';");
	}
	
	public function testget_src_data() {
		$member_addresses = $this->model->get_src_data($this->chunk_id, $this->extract_id);

		$data_hash = md5("10000000"."PRIV"."123 fake st\ntralalala\ntrolololol"."somewhere"."act"."1234"."AA");

		$this->assertNotEmpty($member_addresses);
		$this->assertNotEmpty($member_addresses['10000000']);
		$this->assertNotEmpty($member_addresses['10000000'][$data_hash]);
		$this->assertEquals("PRIV", $member_addresses['10000000'][$data_hash]['type']);
		$this->assertEquals("123 fake st\ntralalala\ntrolololol", $member_addresses['10000000'][$data_hash]['address']);
		$this->assertEquals("somewhere", $member_addresses['10000000'][$data_hash]['suburb']);
		$this->assertEquals("act", $member_addresses['10000000'][$data_hash]['state']);
		$this->assertEquals("1234", $member_addresses['10000000'][$data_hash]['postcode']);
		$this->assertEquals("AA", $member_addresses['10000000'][$data_hash]['country']);
	}
}

?>