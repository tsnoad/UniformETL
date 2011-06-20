<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_names.php");
require_once("/etc/uniformetl/transform/transform_models/member_emails.php");
require_once("/etc/uniformetl/transform/transform_models/member_passwords.php");
require_once("/etc/uniformetl/transform/transform_models/member_web_statuses.php");
require_once("/etc/uniformetl/transform/transform_models/member_confluence_statuses.php");

class MemberConfluenceStatusesGetSrcTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->model = new MemberConfluenceStatuses;

		$this->user_model = new MemberIds;
		$this->name_model = new MemberNames;
		$this->email_model = new MemberEmails;
		$this->password_model = new MemberPasswords;
		$this->web_status_model = new MemberWebStatuses;

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

		$this->name_model->add_data(array("member_id" => "10000000", "type" => "PREF", "given_names" => "Some", "family_name" => "Name"));
		$this->email_model->add_data(array("member_id" => "10000000", "email" => "someone@example.com"));
		$this->password_model->add_data(array("member_id" => "10000000", "password" => "foobar123"));
		$this->web_status_model->add_data("10000000");
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
		runq("DELETE FROM extract_processes WHERE extract_id='".pg_escape_string($this->extract_id)."';");
	}
	
	public function testget_src_data() {
		$member_statuses = $this->model->get_src_data($this->chunk_id);

		$this->assertNotEmpty($member_statuses);
		$this->assertNotEmpty($member_statuses['10000000']);
		$this->assertEquals("10000000", $member_statuses['10000000']['member_id']);
		$this->assertEquals("somename", $member_statuses['10000000']['cn']);
		$this->assertEquals("Name", $member_statuses['10000000']['sn']);
		$this->assertEquals("Some", $member_statuses['10000000']['givenname']);
		$this->assertEquals("someone@example.com", $member_statuses['10000000']['mail']);
		$this->assertNotEmpty($member_statuses['10000000']['userpassword']);
	}
}

?>