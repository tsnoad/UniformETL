<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/extract/extractors/full/extract_launcher.php");

class ExtractFullLauncherTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->launcher = new ExtractFullLauncher;
	}

	protected function tearDown() {
	}

	public function testcalc_time_difference() {
		$dump_query_rows = array(
			time(),
			"someline1",
			"someline2",
			"someline3"
		);

		list($dump_query_rows, $time_difference) = $this->launcher->calc_time_difference($dump_query_rows);

		$this->assertNotEmpty($dump_query_rows);
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $dump_query_rows);
		$this->assertEquals(array("someline1", "someline2", "someline3"), $dump_query_rows);

        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $time_difference);
	}

	//timestamp must be valid
	public function testcalc_time_differenceBadTimestamp() {
		$dump_query_rows = array(
			"blargh",
			"someline1",
			"someline2",
			"someline3"
		);

		try {
			$this->launcher->calc_time_difference($dump_query_rows);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	//timedifference can't be more than 24 hours
	public function testcalc_time_differenceTooDifferent() {
		$dump_query_rows = array(
			strtotime("1970-06-22 11:08:40"),
			"someline1",
			"someline2",
			"someline3"
		);

		try {
			$this->launcher->calc_time_difference($dump_query_rows);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testcheck_list_formatSuccess() {
		$dump_query_rows = array(
			"/data01/datadump/FoxtrotTableDump20110622.tgz",
			strtotime("2011-06-22 11:08:40"),
			md5("somefilehash")
		);

		$this->launcher->check_list_format($dump_query_rows);
	}

	//make sure that bad file paths are rejected
	public function testcheck_list_formatBadPath() {
		$dump_query_rows = array(
			"blargh",
			strtotime("2011-06-22 11:08:40"),
			md5("somefilehash")
		);

		try {
			$this->launcher->check_list_format($dump_query_rows);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	//make sure that bad modtimes are rejected
	public function testcheck_list_formatBadMTime() {
		$dump_query_rows = array(
			"/data01/datadump/FoxtrotTableDump20110622.tgz",
			"blargh",
			md5("somefilehash")
		);

		try {
			$this->launcher->check_list_format($dump_query_rows);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	//make sure that bad file hashes are rejected
	public function testcheck_list_formatBadHash() {
		$dump_query_rows = array(
			"/data01/datadump/FoxtrotTableDump20110622.tgz",
			strtotime("2011-06-22 11:08:40"),
			"blargh"
		);

		try {
			$this->launcher->check_list_format($dump_query_rows);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testcreate_dump_array() {
		$dump_query_rows = array(
			"/data01/datadump/FoxtrotTableDump20110622.tgz",
			strtotime("2011-06-22 11:08:40"),
			md5("somefilehash")
		);

		$time_difference = 0;

		$files = $this->launcher->create_dump_array($dump_query_rows, $time_difference);

		$this->assertNotEmpty($files);
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $files);
		$this->assertNotEmpty($files[0]);
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $files[0]);
		$this->assertEquals("/data01/datadump/FoxtrotTableDump20110622.tgz", $files[0]['path']);
		$this->assertEquals(strtotime("2011-06-22 11:08:40"), $files[0]['modtime']);
		$this->assertEquals(md5("somefilehash"), $files[0]['md5']);
	}

	//make sure that create_dump_array() correctly processes the time difference, for each file
	public function testcreate_dump_arrayTimeDiff() {
		$dump_query_rows = array(
			"/data01/datadump/FoxtrotTableDump20110622.tgz",
			strtotime("2011-06-22 11:08:40"),
			md5("somefilehash")
		);

		$time_difference = 3600;

		$files = $this->launcher->create_dump_array($dump_query_rows, $time_difference);

		$this->assertEquals(strtotime("2011-06-22 11:08:40") + $time_difference, $files[0]['modtime']);
	}

	//make sure that files are sorted with newest first
	public function testcreate_dump_arraySorting() {
		$dump_query_rows = array(
			"/data01/datadump/FoxtrotTableDump19700622.tgz",
			strtotime("1970-06-22 11:08:40"),
			md5("somefilehash"),
			"/data01/datadump/FoxtrotTableDump20110622.tgz",
			strtotime("2011-06-22 11:08:40"),
			md5("someotherfilehash")
		);

		$time_difference = 0;

		$files = $this->launcher->create_dump_array($dump_query_rows, $time_difference);

		$this->assertEquals("/data01/datadump/FoxtrotTableDump20110622.tgz", $files[0]['path']);
		$this->assertEquals("/data01/datadump/FoxtrotTableDump19700622.tgz", $files[1]['path']);
	}

	public function testdump_too_newSuccess() {
		$file = array(
			"path" => "/data01/datadump/FoxtrotTableDump20110622.tgz",
			"modtime" => strtotime("-10 minutes"),
			"md5" => md5("someotherfilehash")
		);

		$this->launcher->dump_too_new($file);
	}

	//reject files who's mtime is in the last 5 minutes
	public function testdump_too_newFail() {
		$file = array(
			"path" => "/data01/datadump/FoxtrotTableDump20110622.tgz",
			"modtime" => strtotime("-2 minutes"),
			"md5" => md5("someotherfilehash")
		);

		try {
			$this->launcher->dump_too_new($file);
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}

	public function testdump_already_processed() {
		//...
	}

	public function testdump_too_old() {
		//...
	}
}

?>