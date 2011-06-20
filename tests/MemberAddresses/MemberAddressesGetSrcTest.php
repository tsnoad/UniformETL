<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_addresses.php");

class MemberAddressesGetSrcTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberAddresses;

		runq("INSERT INTO dump_address (customerid, addrtypeid, line1, line2, line3, suburb, state, postcode, countryid, valid) VALUES ('10000000', 'PRIV', '123 fake st', 'tralalala', 'trolololol', 'somewhere', 'act', '1234', 'AA', '1');");

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
	}

	protected function tearDown() {
		runq("DELETE FROM dump_address WHERE customerid='10000000';");
		runq("DELETE FROM extract_processes WHERE extract_id='".pg_escape_string($this->extract_id)."';");
	}
	
	public function testget_src_data() {
		$member_addresses = $this->model->get_src_data($this->chunk_id);

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