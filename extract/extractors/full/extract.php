<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/autoload.php");

class ExtractFull {
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
		//convert all of the source files into the right format for postgres
		$this->run_convert($models->sources);

		//get the names of all the columns for the tables we need to create
		$get_columns = New ExtractFullGetColumns;
		$table_columns = $get_columns->start($this->extractuntardir."/taboutUserTableColumns.dat", $models->sources);

		//what indexes will we need to create. ask each model
		$model_indexes = Plugins::hook("extract_index-sql", array());

		$sql = "";
		//generate sql to create tables, and import information from source files
		$sql .= $this->create_copy_sql($table_columns, $models->sources, $models->tables);
		//generate sql to create indexes
		$sql .= $this->index_sql($models->transforms, $model_indexes);
		
		//write the sql to a file
		file_put_contents($this->extractdir."/dump.sql", $sql);
		
		//run the sql file against the database
		if (Conf::$dblang == "pgsql") {
			passthru("psql ".Conf::$dbname." < {$this->extractdir}/dump.sql");
		} else if (Conf::$dblang == "mysql") {
			passthru("mysql -u ".Conf::$dbuser." -p".Conf::$dbpass." ".Conf::$dbname." < {$this->extractdir}/dump.sql");
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
			runq("INSERT INTO extract_processes (extract_id, extractor, extract_pid) VALUES ('".db_escape($this->extract_id)."', 'full', '".db_escape(getmypid())."');");
		
			//record information about the source file
			runq("INSERT INTO extract_full (extract_id, source_path, source_timestamp, source_md5) VALUES ('".db_escape($this->extract_id)."', '".db_escape($source_path)."', '".db_escape($source_timestamp)."', '".db_escape($source_md5)."');");

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
		passthru("scp -i ".Conf::$identity." ".Conf::$server.":{$source_path} {$this->extractuntardir} > /etc/uniformetl/logs/extractlog");
	}

	/*
	 * untar the files that we'll need from the source tar file
	 */
	function run_tar($sources) {
		passthru("tar -xvzf {$this->extractuntardir}/*.tgz -C {$this->extractuntardir} taboutUserTableColumns.dat tabout".implode(".dat tabout", $sources).".dat");
	}

	/*
	 * convert all of the source files into the right format for postgres
	 */
	function run_convert($sources) {
		//loop through all the sources we need
		foreach ($sources as $i => $source) {
			//GroupMember gets special treatment
			if ($source == "GroupMember") {
				//delete all the rows that don't contain the string 6052
				//6052 is the group id of the only group id we care about
				//doing this shaves a 3.4GB file down to 5MB - saves a lot of processing time
				passthru("sed -e '/^[^|]*||[^|]*||\ *6052\ *||/!d' -i {$this->extractuntardir}/tabout{$source}.dat");
			}
	
			//use the reformat script to reformat each file
			//so that we can import the whole file into postgres
			passthru("/etc/uniformetl/extract/extractors/full/aaaaarf.sh {$this->extractuntardir}/tabout{$source}.dat {$this->extractdir}/tabout{$source}.sql");
		}
	}

	/*
	 * generate sql to create tables, and import information from source files
	 */
	function create_copy_sql($table_columns, $sources, $tables) {
		//loop through all the sources we need
		foreach ($sources as $i => $source) {
			//what's the name of the table we're going to create
			//table names include the extract id
			$table = str_replace("%{extract_id}", $this->extract_id, $tables[$i]);

			//create table
			$sql .= db_choose(
				db_pgsql("CREATE TABLE {$table} (\n  ".implode(" TEXT,\n  ", $table_columns[$source])." TEXT\n);\n"), 
				db_mysql("CREATE TABLE {$table} (\n  ".implode(" TEXT,\n  ", $table_columns[$source])." TEXT\n) ENGINE=InnoDB;\n")
			);
		
			//import from source file
			$sql .= db_choose(
				db_pgsql("COPY {$table} (".implode(", ", $table_columns[$source]).") FROM '{$this->extractdir}/tabout{$source}.sql' DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS ".'$$\$$'.";\n\n"),
				db_mysql("LOAD DATA LOCAL INFILE '{$this->extractdir}/tabout{$source}.sql' INTO TABLE {$table} COLUMNS TERMINATED BY '|' ENCLOSED BY '\'';\n\n")
			);
		}

		return $sql;
	}

	/*
	 * generate sql to create indexes
	 */
	function index_sql($models, $model_indexes) {
		//for each model that's enabled
		foreach ($models as $model) {
			//for each index for this model
			foreach ($model_indexes[$model] as $index) {
				//add the extract id, and add the index to the array of indexes
				$indexes[] = str_replace("%{extract_id}", $this->extract_id, $index);
			}
		}
		
		//multiple models might use the same tables, and have the same indexes defined
		//remove the duplicates
		$indexes = array_unique($indexes);
		
		//implode to string
		return implode("\n", $indexes);
	}
}

/*
function doandmonitor($execute, $monitor) {
	$pid = pcntl_fork();
	if ($pid == -1) {
		die('could not fork');
	} else if ($pid) {
		while (pcntl_wait($status, WNOHANG) === 0) {
			sleep(2);
			call_user_func_array($monitor[0], $monitor[1]);
		}
	
		pcntl_wait($status);
	} else {
		call_user_func_array($execute[0], $execute[1]);
		die();
	}
}
*/

/*
function execute_scp($source_path, $extractuntardir) {
	shell_exec("scp -i ".Conf::$identity." ".Conf::$server.":{$source_path} {$extractuntardir} > /etc/uniformetl/logs/extractlog");
}
function monitor_scp($source_path, $extractuntardir) {
	clearstatcache();
	echo floor(filesize($extractuntardir."/".basename($source_path)) / (1024 * 1024))."M\n";
}

doandmonitor(array("execute_scp", array($source_path, $extractuntardir)), array("monitor_scp", array($source_path, $extractuntardir)));
*/


/*
function execute_tar($extractuntardir, $sources) {
	shell_exec("tar -xvzf {$extractuntardir}/*.tgz -C {$extractuntardir} taboutUserTableColumns.dat tabout".implode(".dat tabout", $sources).".dat");
}
function monitor_tar($extractuntardir, $sources) {
	clearstatcache();
	foreach ($sources as $i => $source) {
		if (is_file($extractuntardir."/tabout{$source}.dat")) {
			echo str_pad(floor(sprintf("%u", filesize($extractuntardir."/tabout{$source}.dat")) / (1024 * 1024))."M", 8);
		} else {
			echo str_pad("-", 8);
		}
	}
	echo "\n";
}

doandmonitor(array("execute_tar", array($extractuntardir, $models->sources)), array("monitor_tar", array($extractuntardir, $models->sources)));
*/

/*
function execute_sed($extractuntardir, $extractdir, $sources) {
	foreach ($sources as $i => $source) {
		if ($source == "GroupMember") {
			shell_exec("sed -e '/^[^|]*||[^|]*||\ *6052\ *||/!d' -i {$extractuntardir}/tabout{$source}.dat");
		}

		shell_exec("/etc/uniformetl/extract/extractors/full/aaaaarf.sh {$extractuntardir}/tabout{$source}.dat {$extractdir}/tabout{$source}.sql");
	}
}
function monitor_sed($extractuntardir, $extractdir, $sources) {
	clearstatcache();
	foreach ($sources as $i => $source) {
		if (is_file($extractdir."/tabout{$source}.sql")) {
			echo str_pad(floor(sprintf("%u", filesize($extractdir."/tabout{$source}.sql")) / (1024 * 1024))."M", 8);
		} else {
			echo str_pad("-", 8);
		}
	}
	echo "\n";
}

doandmonitor(array("execute_sed", array($extractuntardir, $extractdir, $models->sources)), array("monitor_sed", array($extractuntardir, $extractdir, $models->sources)));
*/

?>