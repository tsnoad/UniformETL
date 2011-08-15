<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models/member_ids.php");
require_once("/etc/uniformetl/transform/transform_models/member_personals.php");
require_once("/etc/uniformetl/transform/transform_models/member_statuses.php");
require_once("/etc/uniformetl/transform/transform_models/member_names.php");
require_once("/etc/uniformetl/transform/transform_models/member_emails.php");
require_once("/etc/uniformetl/transform/transform_models/member_addresses.php");
require_once("/etc/uniformetl/transform/transform_models/member_passwords.php");
require_once("/etc/uniformetl/transform/transform_models/member_divisions.php");
require_once("/etc/uniformetl/transform/transform_models/member_grades.php");
require_once("/etc/uniformetl/transform/transform_models/member_web_statuses.php");
require_once("/etc/uniformetl/transform/transform_models/member_ecpd_statuses.php");
require_once("/etc/uniformetl/transform/transform_models/member_confluence_statuses.php");

class APITest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->user_model = new MemberIds;
		$this->personal_model = new MemberPersonals;
		$this->status_model = new MemberStatuses;
		$this->name_model = new MemberNames;
		$this->email_model = new MemberEmails;
		$this->address_model = new MemberAddresses;
		$this->password_model = new MemberPasswords;
		$this->division_model = new MemberDivisions;
		$this->grade_model = new MemberGrades;
		$this->ecpd_status_model = new MemberEcpdStatuses;

		$this->user_model->add_data("10000000");
		$this->personal_model->add_data(array("member_id" => "10000000", "gender" => "M", "date_of_birth" => "2011-06-10 13:24:00"));
		$this->status_model->add_data(array("member_id" => "10000000", "member" => "t", "financial" => "t"));
		$this->name_model->add_data(array("member_id" => "10000000", "type" => "PREF", "given_names" => "Some", "family_name" => "Name"));
		$this->email_model->add_data(array("member_id" => "10000000", "email" => "someone@example.com"));
		$this->address_model->add_data(array("member_id" => "10000000", "type" => "PRIV", "address" => "123 fake st", "suburb" => "somewhere", "state" => "act", "postcode" => "1234", "country" => "AA"));
		$this->password_model->add_data(array("member_id" => "10000000", "password" => "foobar123"));
		$this->division_model->add_data(array("member_id" => "10000000", "division" => "CBR"));
		$this->grade_model->add_data(array("member_id" => "10000000", "grade" => "FELL", "chartered" => "t"));
		$this->ecpd_status_model->add_data(array("member_id" => "10000000", "participant" => "t", "coordinator" => "t"));
	}

	protected function tearDown() {
		$this->user_model->delete_data("10000000");
	}

	public function testGetUserSuccess() {
		$api_query = shell_exec("curl -s -i -k https://192.168.25.234/api/users/10000000 --get --data \"api_key=".Conf::$api_key."\"");

		//make sure the right HTTP response code was returned
		$this->assertEquals("HTTP/1.1 200 OK", substr($api_query, 0, strpos($api_query, "\r\n")));

		//where's the split between the header and the response content
		$split = strpos($api_query, "\r\n\r\n");
		$this->assertTrue(is_integer($split));
		$this->assertTrue($split > 0);

		$response = trim(substr($api_query, $split));

		$data = json_decode($response, true);

		$this->assertTrue(is_array($data));
		$this->assertEquals("10000000", $data['member_id']);
		$this->assertEquals("M", $data['gender']);
		$this->assertEquals("2011-06-10 13:24:00", $data['date_of_birth']);

		if (Conf::$dblang == "pgsql") {
			$this->assertEquals("t", $data['member']);
			$this->assertEquals("Fellow", $data['grade']);
			$this->assertEquals("t", $data['chartered']);
			$this->assertEquals("FIEAust CPEng", $data['grade_postnominals']);
			$this->assertEquals("Canberra Division", $data['division']);
		} else if (Conf::$dblang == "mysql") {
			$this->assertEquals("1", $data['chartered']);
			$this->assertEquals("FELL", $data['grade']);
			$this->assertEquals("1", $data['member']);
			$this->assertEquals("", $data['grade_postnominals']);
			$this->assertEquals("CBR", $data['division']);
		}

		$this->assertTrue(is_array($data['names']));
		$this->assertTrue(is_array($data['names']['PREF']));
		$this->assertEquals("Some", $data['names']['PREF']['given_names']);
		$this->assertEquals("Name", $data['names']['PREF']['family_name']);

		$this->assertTrue(is_array($data['emails']));
		$this->assertEquals("someone@example.com", $data['emails'][0]);

		$this->assertTrue(is_array($data['addresses']));
		$this->assertTrue(is_array($data['addresses']['PRIV']));
		$this->assertTrue(is_array($data['addresses']['PRIV'][0]));
		$this->assertEquals("123 fake st", $data['addresses']['PRIV'][0]['address']);
		$this->assertEquals("somewhere", $data['addresses']['PRIV'][0]['suburb']);
		$this->assertEquals("act", $data['addresses']['PRIV'][0]['state']);
		$this->assertEquals("1234", $data['addresses']['PRIV'][0]['postcode']);
		$this->assertEquals("AA", $data['addresses']['PRIV'][0]['country']);
	}

	public function testGetUserBadId() {
		$api_query = shell_exec("curl -s -i -k https://192.168.25.234/api/users/foobar --get --data \"api_key=".Conf::$api_key."\"");

		//make sure the right HTTP response code was returned
		$this->assertEquals("HTTP/1.1 400 Bad Request", substr($api_query, 0, strpos($api_query, "\r\n")));

		//where's the split between the header and the response content
		$split = strpos($api_query, "\r\n\r\n");
		$this->assertTrue(is_integer($split));
		$this->assertTrue($split > 0);

		$response = trim(substr($api_query, $split));

		$this->assertEquals("HTTP/1.1 400 Bad Request", $response);
	}

	public function testGetUserDoesntExist() {
		$api_query = shell_exec("curl -s -i -k https://192.168.25.234/api/users/10000001 --get --data \"api_key=".Conf::$api_key."\"");

		//make sure the right HTTP response code was returned
		$this->assertEquals("HTTP/1.1 404 Not Found", substr($api_query, 0, strpos($api_query, "\r\n")));

		//where's the split between the header and the response content
		$split = strpos($api_query, "\r\n\r\n");
		$this->assertTrue(is_integer($split));
		$this->assertTrue($split > 0);

		$response = trim(substr($api_query, $split));

		$this->assertEquals("HTTP/1.1 404 Not Found", $response);
	}

	public function testUserUpdateSuccess() {
		$api_query = shell_exec("curl -s -i -k curl https://192.168.25.234/api/users/10000000 --data \"api_key=".Conf::$api_key."\" --data \"password=somethingdifferent\"");

		//make sure the right HTTP response code was returned
		$this->assertEquals("HTTP/1.1 200 OK", substr($api_query, 0, strpos($api_query, "\r\n")));

		//where's the split between the header and the response content
		$split = strpos($api_query, "\r\n\r\n");
		$this->assertTrue(is_integer($split));
		$this->assertTrue($split > 0);

		$response = trim(substr($api_query, $split));

		$this->assertEquals("HTTP/1.1 200 OK", $response);

		$password_query = runq("SELECT * FROM passwords WHERE member_id='10000000';");
		$this->assertNotEmpty($password_query, "password was not created");
		$this->assertEquals(md5($password_query[0]['salt']."somethingdifferent"), $password_query[0]['hash'], "password was not set correctly");
	}

	public function testUserUpdateBadId() {
		$api_query = shell_exec("curl -s -i -k curl https://192.168.25.234/api/users/foobar --data \"api_key=".Conf::$api_key."\" --data \"password=somethingdifferent\"");

		//make sure the right HTTP response code was returned
		$this->assertEquals("HTTP/1.1 400 Bad Request", substr($api_query, 0, strpos($api_query, "\r\n")));

		//where's the split between the header and the response content
		$split = strpos($api_query, "\r\n\r\n");
		$this->assertTrue(is_integer($split));
		$this->assertTrue($split > 0);

		$response = trim(substr($api_query, $split));

		$this->assertEquals("HTTP/1.1 400 Bad Request", $response);
	}

/*
	public function testUserUpdateDoesntExist() {
		$api_query = shell_exec("curl -s -i -k curl https://192.168.25.234/api/users/10000001 --data \"api_key=".Conf::$api_key."\" --data \"password=somethingdifferent\"");

		//make sure the right HTTP response code was returned
		$this->assertEquals("HTTP/1.1 404 Not Found", substr($api_query, 0, strpos($api_query, "\r\n")));

		//where's the split between the header and the response content
		$split = strpos($api_query, "\r\n\r\n");
		$this->assertTrue(is_integer($split));
		$this->assertTrue($split > 0);

		$response = trim(substr($api_query, $split));

		$this->assertEquals("HTTP/1.1 404 Not Found", $response);
	}
*/

	public function testUserLoginSuccess() {
		$api_query = shell_exec("curl -s -i -k curl https://192.168.25.234/api/users/10000000/login --data \"api_key=".Conf::$api_key."\" --data \"password=foobar123\"");

		//make sure the right HTTP response code was returned
		$this->assertEquals("HTTP/1.1 200 OK", substr($api_query, 0, strpos($api_query, "\r\n")));

		//where's the split between the header and the response content
		$split = strpos($api_query, "\r\n\r\n");
		$this->assertTrue(is_integer($split));
		$this->assertTrue($split > 0);

		$response = trim(substr($api_query, $split));

		$this->assertEquals("HTTP/1.1 200 OK", $response);
	}

	public function testUserLoginBadId() {
		$api_query = shell_exec("curl -s -i -k curl https://192.168.25.234/api/users/foobar/login --data \"api_key=".Conf::$api_key."\" --data \"password=foobar123\"");

		//make sure the right HTTP response code was returned
		$this->assertEquals("HTTP/1.1 400 Bad Request", substr($api_query, 0, strpos($api_query, "\r\n")));

		//where's the split between the header and the response content
		$split = strpos($api_query, "\r\n\r\n");
		$this->assertTrue(is_integer($split));
		$this->assertTrue($split > 0);

		$response = trim(substr($api_query, $split));

		$this->assertEquals("HTTP/1.1 400 Bad Request", $response);
	}

	public function testUserLoginNoPassword() {
		$api_query = shell_exec("curl -s -i -k curl https://192.168.25.234/api/users/10000000/login --data \"api_key=".Conf::$api_key."\" --data \"password=\"");

		//make sure the right HTTP response code was returned
		$this->assertEquals("HTTP/1.1 400 Bad Request", substr($api_query, 0, strpos($api_query, "\r\n")));

		//where's the split between the header and the response content
		$split = strpos($api_query, "\r\n\r\n");
		$this->assertTrue(is_integer($split));
		$this->assertTrue($split > 0);

		$response = trim(substr($api_query, $split));

		$this->assertEquals("HTTP/1.1 400 Bad Request", $response);
	}

	public function testUserUpdateDoesntExist() {
		$api_query = shell_exec("curl -s -i -k curl https://192.168.25.234/api/users/10000001/login --data \"api_key=".Conf::$api_key."\" --data \"password=foobar123\"");

		//make sure the right HTTP response code was returned
		$this->assertEquals("HTTP/1.1 401 Unauthorized", substr($api_query, 0, strpos($api_query, "\r\n")));

		//where's the split between the header and the response content
		$split = strpos($api_query, "\r\n\r\n");
		$this->assertTrue(is_integer($split));
		$this->assertTrue($split > 0);

		$response = trim(substr($api_query, $split));

		$this->assertEquals("HTTP/1.1 401 Unauthorized", $response);
	}

	public function testUserUpdateWrongPassword() {
		$api_query = shell_exec("curl -s -i -k curl https://192.168.25.234/api/users/10000000/login --data \"api_key=".Conf::$api_key."\" --data \"password=somewrongpassword\"");

		//make sure the right HTTP response code was returned
		$this->assertEquals("HTTP/1.1 401 Unauthorized", substr($api_query, 0, strpos($api_query, "\r\n")));

		//where's the split between the header and the response content
		$split = strpos($api_query, "\r\n\r\n");
		$this->assertTrue(is_integer($split));
		$this->assertTrue($split > 0);

		$response = trim(substr($api_query, $split));

		$this->assertEquals("HTTP/1.1 401 Unauthorized", $response);
	}
}

?>
