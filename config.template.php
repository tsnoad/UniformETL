<?php

class Conf {
	public static $software_path = "/home/uetl/";
	public static $model_path = "/home/uetl/transform/transform_models/";

	/**
	 * $run_extractors: Which extractors do you want to use to get data?
	 * Extractors for normal (non-staging) use are:
	 * ExtractFullLauncher
	 * ExtractLatest
	 */
	public static $run_extractors = array(
		"ExtractFullLauncher",
	);

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
		"MemberNmepStatuses",
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
	#$dblang options: pgsql OR mysql
	public static $dblang = "pgsql";

	//extractors/full/extract_launcher.php
	public static $server = "user@remote.server.com";
	public static $identity = "/home/user/.ssh/id_rsa_something";
	public static $dumps_path = '/data01/datadump/*.tgz';
	public static $dump_path_check_regex = "/^\/data01\/datadump\/FoxtrotTableDump[0-9]{8,8}\.tgz$/";

	//extractors/latest/extract_launcher.php
	public static $sybasestruct = "structure1";
	public static $sybasedbalias = "serveralias";
	public static $sybasedbname = "";
	public static $sybasedbuser = "";
	public static $sybasedbpass = "";

	public static $extractor_config = array(
		"full" => array(
			"server" => "",
			"identity" => "",
			"dumps_path" => "",
			"dump_path_check_regex" => "",
		),
		"latest" => array(
			"sybasestruct" => "structure1",
			"sybasedbalias" => "serveralias",
			"sybasedbname" => "",
			"sybasedbuser" => "",
			"sybasedbpass" => "",
		),
		"full_staging" => array(
			"server" => "",
			"identity" => "",
			"dumps_path" => "",
			"dump_path_check_regex" => "",
			"output_path" => "",
		),
		"latest_staging" => array(
			"sybasestruct" => "structure1",
			"sybasedbalias" => "",
			"sybasedbname" => "",
			"sybasedbuser" => "",
			"sybasedbpass" => "",
			"output_path" => "",
		),
		"full_staged" => array(
			"server" => "",
			"identity" => "",
			"dumps_path" => "",
			"dump_path_check_regex" => "",
		),
		"latest_staged" => array(
			"server" => "",
			"identity" => "",
			"dumps_path" => "",
			"dump_path_check_regex" => "",
		),
	);

	public static $model_config = array(
		"MemberConfluenceStatuses" => array(
			"ldaphost" => "localhost",
			"ldapbasedn" => "dc=my-domain,dc=com",
			"ldapuser" => "",
			"ldappass" => "",
		),
		"MemberSlavePasswords" => array(
			"master_dbhost" => "",
			"master_dbname" => "",
			"master_dbuser" => "",
			"master_dbpass" => "",
		),
	);

	//chunks.php
	public static $chunk_size = 10000;

	public static $member_passwords_dbhost = "";
	public static $member_passwords_dbname = "";
	public static $member_passwords_dbuser = "";
	public static $member_passwords_dbpass = "";

	public static $member_confluence_statuses_ldaphost = "ldaps://localhost/";
	public static $member_confluence_statuses_ldapbasedn = "dc=my-domain,dc=com";
	public static $member_confluence_statuses_ldapuser = "";
	public static $member_confluence_statuses_ldappass = "";

	public static $api_key = "3CEaCHxr8IoTD0NzEpLeGdj6iWRnOr2";

	//what remote hosts are allowed to use the passwords api
	public static $api_pass_rem_hosts = array(
	);

	//Trust levels used for authentication clients
	const API_CLIENT_LOW_TRUST = 0;
	const API_CLIENT_MED_TRUST = 1;
	const API_CLIENT_HIGH_TRUST = 2;

	//ip address patterns for trusted clients
	public static $api_client_trust_levels = array(
		//untrusted hosts. note that these host will still have to authenticate, and that communications will be encrypted
		self::API_CLIENT_LOW_TRUST => array(
			'[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}',
		),
		//moderately trusted hosts
		self::API_CLIENT_MED_TRUST => array(
			'192\.168\.0\.[0-9]{1,3}',
		),
		//highly trusted hosts
		self::API_CLIENT_HIGH_TRUST => array(
		),
	);

	//callback is made to these locations whenever a password is changed
/* 	public static $api_passwd_callback_primary = "192.168.25.16"; */
/* 	public static $api_passwd_callback_secondary = "192.168.25.31"; */

	/*
	 * Reporer Config
	 */
	//command used to send reports via email. %{body} and other placeholders will be replaced with email content
	public static $report_email_cmd = "echo %{body} | mail -s %{subject} %{recipients}";
	//recipients. comma seperated list
	public static $report_email_recipients = "foo@example.com,bar@example.com";
}

?>