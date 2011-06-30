<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/extract/extractors/full/extract.php");

class ExtractFullTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->extract = new ExtractFull;
	}

	protected function tearDown() {
	}

	public function testcheck_argsSuccess() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = "1309401712";
		$source_md5 = md5("somefile");

		$this->extract->check_args($source_path, $source_timestamp, $source_md5);
	}

	public function testcheck_argsNoPath() {
		$source_path = "";
		$source_timestamp = "1309401712";
		$source_md5 = md5("somefile");

		try {
			$this->extract->check_args($source_path, $source_timestamp, $source_md5);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testcheck_argsInvalidPath() {
		$source_path = "/somthing/image.jpg";
		$source_timestamp = "1309401712";
		$source_md5 = md5("somefile");

		try {
			$this->extract->check_args($source_path, $source_timestamp, $source_md5);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testcheck_argsNoTimestamp() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = "";
		$source_md5 = md5("somefile");

		try {
			$this->extract->check_args($source_path, $source_timestamp, $source_md5);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testcheck_argsInvalidTimestamp() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = "squiggles";
		$source_md5 = md5("somefile");

		try {
			$this->extract->check_args($source_path, $source_timestamp, $source_md5);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testcheck_argsNoHash() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = "1309401712";
		$source_md5 = "";

		try {
			$this->extract->check_args($source_path, $source_timestamp, $source_md5);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testcheck_argsInvalidHash() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = "1309401712";
		$source_md5 = "bam";

		try {
			$this->extract->check_args($source_path, $source_timestamp, $source_md5);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testget_extract_id() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = date("Y-m-d H:i:s");
		$source_md5 = md5("somefile");

		$this->extract->get_extract_id($source_path, $source_timestamp, $source_md5);

		$this->assertNotEmpty($this->extract->extract_id);

		$extract_full_query = runq("SELECT * FROM extract_processes e INNER JOIN extract_full ef ON (ef.extract_id=e.extract_id) WHERE e.extract_id='".pg_escape_string($this->extract->extract_id)."';");

		$this->assertNotEmpty($extract_full_query);
		$this->assertNotEmpty($extract_full_query[0]);
		$this->assertEquals($this->extract->extract_id, $extract_full_query[0]['extract_id']);
		$this->assertEquals("f", $extract_full_query[0]['finished']);
		$this->assertEquals("f", $extract_full_query[0]['failed']);
/* 		$this->assertEquals("f", $extract_full_query[0]['models']); */
		$this->assertEquals("full", $extract_full_query[0]['extractor']);
		$this->assertEquals(getmypid(), $extract_full_query[0]['extract_pid']);
		$this->assertEquals($source_path, $extract_full_query[0]['source_path']);
		$this->assertEquals($source_timestamp, $extract_full_query[0]['source_timestamp']);
		$this->assertEquals($source_md5, $extract_full_query[0]['source_md5']);

		runq("DELETE FROM extract_processes WHERE extract_id='".pg_escape_string($this->extract->extract_id)."';");
	}

	public function testget_extractdir() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = date("Y-m-d H:i:s");
		$source_md5 = md5("somefile");

		$this->extract->get_extract_id($source_path, $source_timestamp, $source_md5);

		$this->extract->get_extractdir();

		$this->assertNotEmpty($this->extract->extractdir);

		$this->assertTrue(is_dir($this->extract->extractdir));

		shell_exec("rm -r ".$this->extract->extractdir);
	}

	public function test_get_extractuntardir() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = date("Y-m-d H:i:s");
		$source_md5 = md5("somefile");

		$this->extract->get_extract_id($source_path, $source_timestamp, $source_md5);

		$this->extract->get_extractdir();

		$this->extract->get_extractuntardir();

		$this->assertNotEmpty($this->extract->extractuntardir);

		$this->assertTrue(is_dir($this->extract->extractuntardir));

		shell_exec("rm -r ".$this->extract->extractdir);
	}

	public function test_get_extractuntardirFail() {
		$source_path = "/somthing/something.tgz";
		$source_timestamp = date("Y-m-d H:i:s");
		$source_md5 = md5("somefile");

		$this->extract->get_extract_id($source_path, $source_timestamp, $source_md5);

		try {
			$this->extract->get_extractuntardir();
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}
}

?>