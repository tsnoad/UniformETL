<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/autoload.php");

class ExtractLatestStaged {
	public $extract_id;
	public $extractdir;
	public $extractuntardir;

	function start($source_path, $source_timestamp, $source_md5) {
		try {
			//make sure we were provided with all the arguments that we need
			$this->check_args($source_path, $source_timestamp, $source_md5);
		} catch (Exception $e) {
			die($e->getMessage());
		}

		//reserve an extract id, and create an extract process in the database
		$this->get_extract_id($source_path, $source_timestamp, $source_md5);
		//create the folders where we're going to put the files we're working on
		$this->get_extractdir();
		$this->get_extractuntardir();

		//find out what tables and table sources we're going to need need
		$models = New Models;
		$models->start();

		//get the specified source file from the remote server 
		$this->run_scp($source_path);
		//untar the files that we'll need from the source tar file
		$this->run_tar($models->sources);

		if (!is_file("{$this->extractdir}/dump.sql") || !is_file("{$this->extractdir}/member_ids.json")) {
			die("no files?");
		}

		passthru("sed -E -e 's/\%\{extract_id\}/{$this->extract_id}/g' -i {$this->extractdir}/dump.sql");

		$member_ids_json = file_get_contents("{$this->extractdir}/member_ids.json");
		$member_ids = json_decode($member_ids_json, true);

		if (empty($member_ids)) {
			die("no member ids?");
		}

		$this->save_member_ids($member_ids);
		
		//run the sql file against the database
		if (Conf::$dblang == "pgsql") {
			passthru("psql ".Conf::$dbname." < {$this->extractdir}/dump.sql 2>&1", $return_state);

			var_dump($return_state);
		} else if (Conf::$dblang == "mysql") {
			passthru("mysql -u ".Conf::$dbuser." -p".Conf::$dbpass." ".Conf::$dbname." < {$this->extractdir}/dump.sql 2>&1");
		}
		
		try {
			//let the database know we've finished the extract
			runq("UPDATE extract_processes SET finished=TRUE, finish_date=now() WHERE extract_id='".db_escape($this->extract_id)."';");
		} catch (Exception $e) {
			die($e->getMessage());
		}
		
		//helpful log message
		echo "finished\n";
	}

	/*
	 * make sure we were provided with all the arguments that we need
	 */
	function check_args($source_path, $source_timestamp, $source_md5) {
		//make sure the source path looks valid
		if (empty($source_path) || !is_string($source_path) || substr($source_path, -4, 4) != ".tgz") {
			throw new Exception("source_path is not valid");
		}

		//make sure the source mtime timestamp looks valid
		if (empty($source_timestamp) || !is_string($source_timestamp) || !strtotime($source_timestamp)) {
			throw new Exception("source_timestamp is not valid");
		}
		
		//make sure the source hash looks like a md5 hash
		if (preg_match("/^[0-9a-zA-Z]{32,32}$/", $source_md5) < 1) {
			throw new Exception("source_md5 is not valid");
		}
	}

	/*
	 * reserve an extract id, and create an extract process in the database
	 */
	function get_extract_id($source_path, $source_timestamp, $source_md5) {
		try {
			//reserve a process id so other methods can use it
			$this->extract_id = db_nextval("extract_processes", "extract_id");
		
			//create a process in the database
			runq("INSERT INTO extract_processes (extract_id, extractor, extract_pid, models) VALUES ('".db_escape($this->extract_id)."', 'latest_staged', '".db_escape(getmypid())."', '".db_escape(json_encode(Conf::$do_transforms))."');");
		
			//record information about the source file
			runq("INSERT INTO extract_latest_staged (extract_id, source_path, source_timestamp, source_md5) VALUES ('".db_escape($this->extract_id)."', '".db_escape($source_path)."', '".db_escape($source_timestamp)."', '".db_escape($source_md5)."');");

		} catch (Exception $e) {
			print_r($e->getMessage());
			die("could not create process in database");
		}
	}

	/*
	 * Create a folder where we can put the source files while we work on them
	 */
	function get_extractdir() {
		//so other methods know where the folder is
		$this->extractdir = Conf::$software_path."extract/extract_processes/{$this->extract_id}";

		//create the folder
		if (!mkdir($this->extractdir)) {
			die("could not create dump folder");
		}
	}

	/*
	 * Create a folder where we can put the source files while we work on them
	 */
	function get_extractuntardir() {
		//so other methods know where the folder is
		$this->extractuntardir = Conf::$software_path."extract/extract_processes/{$this->extract_id}/untar";

		//create the folder
		if (!mkdir($this->extractuntardir)) {
			die("could not create dump untar folder");
		}
	}

	/*
	 * get the specified source file from the remote server
	 */
	function run_scp($source_path) {
		passthru("scp -i ".Conf::$extractor_config['latest_staged']['identity']." ".Conf::$extractor_config['latest_staged']['server'].":{$source_path} {$this->extractuntardir} > /etc/uniformetl/logs/extractlog");
	}

	/*
	 * untar the files that we'll need from the source tar file
	 */
	function run_tar($sources) {
		passthru("tar -xvzf {$this->extractuntardir}/*.tgz -C {$this->extractdir} dump.sql member_ids.json");
	}

	function save_member_ids($member_ids) {
		try {
			runq("UPDATE extract_latest_staged SET member_ids='".db_escape(json_encode(array_values($member_ids)))."' WHERE extract_id='".db_escape($this->extract_id)."';");
		} catch (Exception $e) {
			print_r($e->getMessage());
			die("could not save process");
		}
	}
}

?>