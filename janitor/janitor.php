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
				echo "dropping table {$tablename}\n";
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
				echo "removing directory {$dirname}\n";
				try {
					$this->remove_dir($finished_dir);
				} catch (Exception $e) {
					die("could not remove dir {$finished_table}.");
				}
			}
		}
	}

	function get_finished_extracts() {
		//get all extracts that are complete,
		//have at least one completed transform,
		//and have no incomplete transforms
		$finished_extracts = runq("select distinct e.extract_id from extract_processes e inner join transform_processes t on (t.extract_id=e.extract_id) left outer join transform_processes t2 on (t2.extract_id=t.extract_id and t2.transform_id!=t.transform_id and t2.finished=false) where t.transform_id is not null and t.finished=true and t2.transform_id is null;");

		if (empty($finished_extracts)) return null;
		
		//create array of transform ids
		$extract_ids = array_map(create_function('$a', 'return $a["extract_id"];'), $finished_extracts);

		return $extract_ids;
	}

	function get_finished_tables($extract_ids) {
		//get all the tables in the database that belong to one of the complete extracts
		$fin_dump_table = "tablename ~ '^dump_(".implode("|", $extract_ids).")_[a-zA-Z]+$'";
		$finished_tables = runq("select tablename from pg_tables where {$fin_dump_table};");

		if (empty($finished_tables)) return null;

		//create array of table names
		$tables_names = array_map(create_function('$a', 'return $a["tablename"];'), $finished_tables);

		return $tables_names;
	}

	function drop_table($tablename) {		
		if (preg_match("/^dump_[0-9]+_[a-zA-Z]+$/", $tablename) !== 1) {
			throw new Exception("cant drop table {$tablename}. invalid name");
		}

		runq("drop table {$tablename};");
	}

	function get_finished_dirs($extract_ids) {
		$extract_dirs = scandir(Conf::$software_path."extract/extract_processes/");

		foreach ($extract_dirs as $extract_dir) {
			//filter out . and ..
			if (!preg_match("/^[0-9]+$/", $extract_dir)) continue;
		
			if (preg_match("/^(".implode("|", $extract_ids).")$/", $extract_dir)) {
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
}

?>