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

	//extract.php
	//where are the dump files
	public $dump_path = "/home/user/hotel/extract/extract_processes/";

	public $model_path = "/home/user/hotel/transform/transform_models/";
}

?>