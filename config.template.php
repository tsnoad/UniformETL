<?php

class Conf {
	//database.php
	public $dbhost = "localhost";
	public $dbname = "hotel";
	public $dbuser = "";
	public $dbpass = "";

	//extract_launcher.php
	public $server = "user@remote.server.com";
	public $identity = "/home/user/.ssh/id_rsa_foxrep";
	public $dumps_path = '/data01/datadump/*.tgz';
	public $dump_path_check_regex = "/^\/data01\/datadump\/FoxtrotTableDump[0-9]{8,8}\.tgz$/";

	public $software_path = "/home/user/hotel/";

	public $model_path = "/home/user/hotel/transform/transform_models/";

/* 	public $do_transforms = array("member_ids", "passwords", "names", "emails", "addresses", "web_statuses", "ecpd_statuses", "confluence_statuses", "invoices", "receipts"); */
	public $do_transforms = array("confluence_statuses");
}

?>