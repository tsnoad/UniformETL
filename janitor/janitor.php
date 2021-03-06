<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");

Class Janitor {
	function start() {
		//is this script already running?
		if (trim(shell_exec("ps h -C run_janitor.php o pid | wc -l")) > 1) {
			//let's not step on our own toes
			die("janitor is currently running\n");
		}

		//are there any extracts currently in progress?
		$already_extracting = runq("SELECT count(*) as count FROM extract_processes WHERE finished=FALSE;");
		if ($already_extracting[0]['count'] > 0) {
			//we have to wait until they've finished
			die("extract is currently running\n");
		}

		//are there any transforms currently in progress?
		$already_transforming = runq("SELECT count(*) as count FROM transform_processes WHERE finished=FALSE;");
		if ($already_transforming[0]['count'] > 0) {
			//we have to wait until they've finished
			die("transform is currently running\n");
		}

		try {
			$extract_ids = $this->get_finished_extracts();
		} catch (Exception $e) {
			die("could not get complete extracts.");
		}

		if (empty($extract_ids)) {
			die("nothing needs to be done.");
		}

		try {
			$finished_tables = $this->get_finished_tables($extract_ids);
		} catch (Exception $e) {
			die("could not get tables for complete extracts.");
		}

		if (!empty($finished_tables)) {
			foreach ($finished_tables as $finished_table) {
				echo "dropping table {$finished_table}\n";
				try {
					$this->drop_table($finished_table);
				} catch (Exception $e) {
					die("could not drop table {$finished_table}.");
				}
			}
		}

		try {
			$finished_dirs = $this->get_finished_dirs($extract_ids);
		} catch (Exception $e) {
			die("could not get dirs for complete extracts.");
		}

		if (!empty($finished_dirs)) {
			foreach ($finished_dirs as $finished_dir) {
				echo "removing directory {$finished_dir}\n";
				try {
					$this->remove_dir($finished_dir);
				} catch (Exception $e) {
					die("could not remove dir {$finished_table}.");
				}
			}
		}

		try {
			$finished_full_staging_dumps = $this->get_finished_full_staging_dumps($extract_ids);
		} catch (Exception $e) {
			die("could not get staging dumps for complete extracts.");
		}

		if (!empty($finished_full_staging_dumps)) {
			foreach ($finished_full_staging_dumps as $finished_full_staging_dump) {
				echo "removing staging dump {$finished_full_staging_dump}\n";
				try {
					$this->remove_full_staging_dump($finished_full_staging_dump);
				} catch (Exception $e) {
					die("could not remove staging dump {$finished_full_staging_dump}.");
				}
			}
		}

		try {
			$finished_latest_staging_dumps = $this->get_finished_latest_staging_dumps($extract_ids);
		} catch (Exception $e) {
			die("could not get staging dumps for complete extracts.");
		}

		if (!empty($finished_latest_staging_dumps)) {
			foreach ($finished_latest_staging_dumps as $finished_latest_staging_dump) {
				echo "removing staging dump {$finished_latest_staging_dump}\n";
				try {
					$this->remove_latest_staging_dump($finished_latest_staging_dump);
				} catch (Exception $e) {
					die("could not remove staging dump {$finished_latest_staging_dump}.");
				}
			}
		}
	}

	function get_finished_extracts() {
		//get all extracts that are complete,
		//have at least one completed transform,
		//and have no incomplete transforms
		$finished_extracts = runq("select distinct e.extract_id from extract_processes e left outer join transform_processes t on (t.extract_id=e.extract_id) left outer join transform_processes t2 on (t2.extract_id=t.extract_id and t2.transform_id!=t.transform_id and t2.finished=false) where (t.transform_id is not null and t.finished=true and t2.transform_id is null) or (e.finished=true and e.finish_date<".db_choose(db_pgsql("now() - INTERVAL '24 hours'"), db_mysql("date_sub(now(), INTERVAL 24 hour)"))." and (e.extractor='full_staging' or e.extractor='latest_staging'));");

		if (empty($finished_extracts)) return null;
		
		//create array of transform ids
		$extract_ids = array_map(create_function('$a', 'return $a["extract_id"];'), $finished_extracts);

		return $extract_ids;
	}

	function get_finished_tables($extract_ids) {
		//get all the dump tables in the database
		if (Conf::$dblang == "pgsql") {
			$dump_table_regex = "tablename ~ '^dump_[0-9]+_[a-zA-Z]+$'";
			$dump_tables = runq("select tablename from pg_tables where {$dump_table_regex};");
		} else if (Conf::$dblang == "mysql") {
			$dump_table_regex = "table_name REGEXP '^dump_[0-9]+_[a-zA-Z]+$'";
			$dump_tables = runq("select table_name as tablename from information_schema.tables where {$dump_table_regex};");
		}

		if (empty($dump_tables)) return null;

		//create array of table names
		$tables_names = array_map("reset", $dump_tables);

		//find which dump tables belong to complete extracts
		foreach ($tables_names as $table_name) {
			//get the extract id from the table name
			unset($table_extract_id);
			$table_extract_id = preg_replace('/^dump_([0-9]+)_[a-zA-Z]+$/', '$1', $table_name);
			if (empty($table_extract_id)) continue;

			if (in_array($table_extract_id, $extract_ids)) {
				$finished_tables[] = $table_name;
			}
		}

		return $finished_tables;
	}

	function drop_table($tablename) {		
		if (preg_match("/^dump_[0-9]+_[a-zA-Z]+$/", $tablename) !== 1) {
			throw new Exception("cant drop table {$tablename}. invalid name");
		}

		runq("drop table {$tablename};");
	}

	function get_finished_dirs($extract_ids) {
		$extract_dirs = scandir(Conf::$software_path."extract/extract_processes/");

		$finished_dirs = array();

		foreach ($extract_dirs as $extract_dir) {
			//filter out . and ..
			if (!preg_match("/^[0-9]+$/", $extract_dir)) continue;
		
			if (in_array($extract_dir, $extract_ids)) {
				$finished_dirs[] = $extract_dir;
			}
		}

		return $finished_dirs;
	}

	function remove_dir($dirname) {
		if (preg_match("/^[0-9]+$/", $dirname) !== 1) {
			throw new Exception("cant remove dir {$dirname}. invalid name");
		}

		shell_exec("rm -r ".escapeshellarg(Conf::$software_path."extract/extract_processes/".$dirname));
	}

	function get_finished_full_staging_dumps($extract_ids) {
		if (!isset(Conf::$extractor_config['full_staging']['output_path']) || !is_dir(Conf::$extractor_config['full_staging']['output_path'])) return null;

		$extract_dumps = scandir(Conf::$extractor_config['full_staging']['output_path']);

		$finished_dumps = array();

		foreach ($extract_dumps as $extract_dump) {
			//filter out . and ..
			if (!preg_match("/^dump_[0-9]+\.tgz$/", $extract_dump)) continue;
		
			if (preg_match("/^dump_(".implode("|", $extract_ids).")\.tgz$/", $extract_dump)) {
				$finished_dumps[] = $extract_dump;
			}
		}

		return $finished_dumps;
	}

	function remove_full_staging_dump($dirname) {		
		if (preg_match("/^dump_[0-9]+\.tgz$/", $dirname) !== 1) {
			throw new Exception("cant remove staging dump {$dirname}. invalid name");
		}

		shell_exec("rm -r ".escapeshellarg(Conf::$extractor_config['full_staging']['output_path'].$dirname));
	}

	function get_finished_latest_staging_dumps($extract_ids) {
		if (!isset(Conf::$extractor_config['latest_staging']['output_path']) || !is_dir(Conf::$extractor_config['latest_staging']['output_path'])) return null;

		$extract_dumps = scandir(Conf::$extractor_config['latest_staging']['output_path']);

		$finished_dumps = array();

		foreach ($extract_dumps as $extract_dump) {
			//filter out . and ..
			if (!preg_match("/^dump_[0-9]+\.tgz$/", $extract_dump)) continue;
		
			if (preg_match("/^dump_(".implode("|", $extract_ids).")\.tgz$/", $extract_dump)) {
				$finished_dumps[] = $extract_dump;
			}
		}

		return $finished_dumps;
	}

	function remove_latest_staging_dump($dirname) {		
		if (preg_match("/^dump_[0-9]+\.tgz$/", $dirname) !== 1) {
			throw new Exception("cant remove staging dump {$dirname}. invalid name");
		}

		shell_exec("rm -r ".escapeshellarg(Conf::$extractor_config['latest_staging']['output_path'].$dirname));
	}
}

?>