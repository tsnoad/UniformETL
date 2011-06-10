<?php

class Conf {
	public $software_path = "/home/uetl/";
	public $model_path = "/home/uetl/transform/transform_models/";

	public $do_transforms = array(
		"MemberIds",
		"MemberPasswords",
		"MemberPersonals",
		"MemberNames",
		"MemberEmails",
		"MemberAddresses",
		"MemberWebStatuses",
		"MemberEcpdStatuses",
		"MemberConfluenceStatuses",
		"MemberInvoices",
		"MemberReceipts",
		"MemberGrades",
		"MemberDivisions"
	);

	//database.php
	public $dbhost = "localhost";
	public $dbname = "hotel";
	public $dbuser = "";
	public $dbpass = "";

	//extract_launcher.php
	public $server = "user@remote.server.com";
	public $identity = "/home/user/.ssh/id_rsa_something";
	public $dumps_path = '/data01/datadump/*.tgz';
	public $dump_path_check_regex = "/^\/data01\/datadump\/FoxtrotTableDump[0-9]{8,8}\.tgz$/";

	public $member_passwords_dbhost = "";
	public $member_passwords_dbname = "";
	public $member_passwords_dbuser = "";
	public $member_passwords_dbpass = "";

	public $member_confluence_statuses_ldaphost = "localhost";
	public $member_confluence_statuses_ldapbasedn = "dc=my-domain,dc=com";
	public $member_confluence_statuses_ldapuser = "";
	public $member_confluence_statuses_ldappass = "";

	public $api_key = "3CEaCHxr8IoTD0NzEpLeGdj6iWRnOr2";
}

?>