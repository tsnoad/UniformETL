#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/autoload.php");

class ExtractFull {
	function start($source_path, $source_timestamp, $source_md5) {
		$this->check_args($source_path, $source_timestamp, $source_md5);

		$extract_id = $this->get_extract_id($source_path, $source_timestamp, $source_md5);
		$extractdir = $this->get_extractdir($extract_id);
		$extractuntardir = $this->get_extractuntardir($extract_id);

		$models = New Models;
		$models->start();

		$this->run_scp($source_path, $extractuntardir);
		$this->run_tar($models->sources, $extractuntardir);
		$this->run_convert($models->sources, $extractdir, $extractuntardir);

		$get_columns = New ExtractFullGetColumns;
		$table_columns = $get_columns->start($extractuntardir."/taboutUserTableColumns.dat", $models->sources);

		$model_indexes = Plugins::hook("extract_index-sql", array());

		$sql = "";
		$sql .= $this->create_copy_sql($extract_id, $extractdir, $table_columns, $models->sources, $models->tables);
		$sql .= $this->index_sql($extract_id, $models->transforms, $model_indexes);
		
		file_put_contents($extractdir."/dump.sql", $sql);
		
		var_dump(passthru("psql hotel < {$extractdir}/dump.sql"));
		
		runq("UPDATE extract_processes SET finished=TRUE, finish_date=now() WHERE extract_id='".pg_escape_string($extract_id)."';");
		
		echo "finished\n";
	}

	function check_args($source_path, $source_timestamp, $source_md5) {
		if (!is_string($source_path) && substr($source_path, -4, 4) != ".tgz") {
			die("source_path is not valid");
		}
		
		if (!is_string($source_timestamp)) {
			die("source_timestamp is not valid");
		}
		
		if (preg_match("/^[0-9a-zA-Z]{32,32}$/", $source_md5) < 1) {
			die("source_md5 is not valid");
		}
	}

	function get_extract_id($source_path, $source_timestamp, $source_md5) {
		try {
			//create a process in the uetl database
			$extract_id_query = runq("SELECT nextval('extract_processes_extract_id_seq');");
		
			$extract_id = $extract_id_query[0]['nextval'];
		
			runq("INSERT INTO extract_processes (extract_id, extractor, extract_pid) VALUES ('".pg_escape_string($extract_id)."', 'full', '".pg_escape_string(getmypid())."');");
		
			runq("INSERT INTO extract_full (extract_id, source_path, source_timestamp, source_md5) VALUES ('".pg_escape_string($extract_id)."', '".pg_escape_string($source_path)."', '".pg_escape_string($source_timestamp)."', '".pg_escape_string($source_md5)."');");
		} catch (Exception $e) {
			die("could not create process in database");
		}

		return $extract_id;
	}

	function get_extractdir($extract_id) {
		$extractdir = Conf::$software_path."extract/extract_processes/{$extract_id}";

		if (!mkdir($extractdir)) {
			die("could not create dump folder");
		}

		return $extractdir;
	}

	function get_extractuntardir($extract_id) {
		$extractuntardir = Conf::$software_path."extract/extract_processes/{$extract_id}/untar";

		if (!mkdir($extractuntardir)) {
			die("could not create dump untar folder");
		}

		return $extractuntardir;
	}

	function run_scp($source_path, $extractuntardir) {
		passthru("scp -i ".Conf::$identity." ".Conf::$server.":{$source_path} {$extractuntardir} > /etc/uniformetl/logs/extractlog");
	}

	function run_tar($sources, $extractuntardir) {
		passthru("tar -xvzf {$extractuntardir}/*.tgz -C {$extractuntardir} taboutUserTableColumns.dat tabout".implode(".dat tabout", $sources).".dat");
	}

	function run_convert($sources, $extractdir, $extractuntardir) {
		foreach ($sources as $i => $source) {
			if ($source == "GroupMember") {
				passthru("sed -e '/^[^|]*||[^|]*||\ *6052\ *||/!d' -i {$extractuntardir}/tabout{$source}.dat");
			}
	
			passthru("/etc/uniformetl/extract/extractors/full/aaaaarf.sh {$extractuntardir}/tabout{$source}.dat {$extractdir}/tabout{$source}.sql");
		}
	}

	function create_copy_sql($extract_id, $extractdir, $table_columns, $sources, $tables) {
		foreach ($sources as $i => $source) {
			$table = str_replace("%{extract_id}", $extract_id, $tables);
		
			$sql .= "CREATE TABLE {$table} (\n  ".implode(" TEXT,\n  ", $table_columns[$source])." TEXT\n);\n";
		
			$sql .= "COPY {$table} (".implode(", ", $table_columns[$source]).") FROM '{$extractdir}/tabout{$source}.sql' DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS ".'$$\$$'.";\n\n";
		}

		return $sql;
	}

	function index_sql($extract_id, $models, $model_indexes) {
		foreach ($models as $model) {
			foreach ($model_indexes[$model] as $index) {
				$indexes[] = str_replace("${extract_id}", $extract_id, $index);
			}
		}
		
		$indexes = array_unique($indexes);
		
		return implode("\n", $indexes);
	}
}

$source_path = $_SERVER["argv"][1];
$source_timestamp = $_SERVER["argv"][2];
$source_md5 = $_SERVER["argv"][3];

$extract = New ExtractFull;
$extract->start($source_path, $source_timestamp, $source_md5);


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