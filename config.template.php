<?php

class Conf {
	public static $software_path = "/home/uetl/";
	public static $model_path = "/home/uetl/transform/transform_models/";

	public static $do_transforms = array(
		"MemberIds",
		"MemberPasswords",
		"MemberPersonals",
		"MemberSecretQuestions",
		"MemberStatuses",
		"MemberNames",
		"MemberEmails",
		"MemberAddresses",
		"MemberWebStatuses",
		"MemberEcpdStatuses",
		"MemberEpdpStatuses",
		"MemberConfluenceStatuses",
		"MemberInvoices",
		"MemberInvoiceItems",
		"MemberReceipts",
		"MemberReceiptAllocations",
		"MemberGrades",
		"MemberDivisions",
		"MemberColleges",
		"MemberSocieties"
	);

	//database.php
	public static $dbhost = "localhost";
	public static $dbname = "hotel";
	public static $dbuser = "";
	public static $dbpass = "";
	public static $dblang = "pgsql";
/* 	public static $dblang = "mysql"; */

	//extract_launcher.php
	public static $server = "user@remote.server.com";
	public static $identity = "/home/user/.ssh/id_rsa_something";
	public static $dumps_path = '/data01/datadump/*.tgz';
	public static $dump_path_check_regex = "/^\/data01\/datadump\/FoxtrotTableDump[0-9]{8,8}\.tgz$/";

	//chunks.php
	public static $chunk_size = 10000;

	public static $member_passwords_dbhost = "";
	public static $member_passwords_dbname = "";
	public static $member_passwords_dbuser = "";
	public static $member_passwords_dbpass = "";

	public static $member_confluence_statuses_ldaphost = "localhost";
	public static $member_confluence_statuses_ldapbasedn = "dc=my-domain,dc=com";
	public static $member_confluence_statuses_ldapuser = "";
	public static $member_confluence_statuses_ldappass = "";

	public static $api_key = "3CEaCHxr8IoTD0NzEpLeGdj6iWRnOr2";
}

?>