<?php

class Conf {
	//database.php
	public $dbname = "hotel";
	public $user = "user";

	//extract_launcher.php
	public $server = "easysadmin@foxrep.nat.internal";
	public $identity = "/home/user/.ssh/id_rsa_foxrep";
	public $dumps_path = '/data01/datadump/*.tgz';
	public $dump_path_check_regex = "/^\/data01\/datadump\/FoxtrotTableDump[0-9]{8,8}\.tgz$/";

/*
	public $identity = "";
	public $server = "golf@eacbr-db1.nat.internal";
	public $dumps_path = '/var/golf/foxtrot_dump/*201101*.tgz';
	public $dump_path_check_regex = "/^\/var\/golf\/foxtrot_dump\/FoxtrotTableDump[0-9]{8,8}\.tgz$/";
*/

	public $software_path = "/home/user/hotel/";

	//extract.php
	//where are the dump files
/* 	public $dump_path = $software_path."extract/extract_processes/"; */

	public $model_path = "/home/user/hotel/transform/transform_models/";

	public $do_transforms = array("member_ids", "passwords", "names", "emails", "addresses", "web_statuses", "ecpd_statuses", "confluence_statuses", "invoices");
/* 	public $do_transforms = array("invoices"); */
}

?>