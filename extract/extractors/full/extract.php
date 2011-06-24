#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/autoload.php");

$models = New Models;
$models->start();

print_r($models->transforms);
print_r($models->tables);
print_r($models->sources);

var_dump("tar -xvzf somfile.tar -C ".Conf::$software_path."extract/extract/processes/".rand(0, 100)."/untar/ tabout".implode(".dat tabout", $models->sources).".dat");

foreach ($models->sources as $source) {
	var_dump(Conf::$software_path."extract/extract/extractors/full/recode.sh ".Conf::$software_path."extract/extract/processes/".rand(0, 100)."/untar/tabout{$source}.dat ".Conf::$software_path."extract/extract/processes/".rand(0, 100)."/{$source}.sql");
}

foreach ($models->sources as $i => $source) {
	var_dump("COPY ".str_replace("%{extract_id}", rand(0, 100), $models->tables[$i])." FROM ".Conf::$software_path."extract/extract/processes/".rand(0, 100)."/{$source}.sql");
}

$source_path = $_SERVER["argv"][0];
$source_timestamp = $_SERVER["argv"][1];
$source_md5 = $_SERVER["argv"][2];

$source_path = "/data01/datadump/FoxtrotTableDump20110623.tgz";
$source_timestamp = "2011/06/23 12:00:00";
$source_md5 = md5(rand());

/*
if (preg_match("/^[0-9]+$/", $extract_id) < 1) {
	die("extract_id is not valid");
}
*/

if (!is_string($source_path) && substr($source_path, -4, 4) != ".tgz") {
	die("source_path is not valid");
}

if (!is_string($source_timestamp)) {
	die("source_timestamp is not valid");
}

if (preg_match("/^[0-9a-zA-Z]{32,32}$/", $source_md5) < 1) {
	die("source_md5 is not valid");
}

try {
	//create a process in the uetl database
	$extract_id_query = runq("SELECT nextval('extract_processes_extract_id_seq');");

	$extract_id = $extract_id_query[0]['nextval'];

	runq("INSERT INTO extract_processes (extract_id, extractor, extract_pid) VALUES ('".pg_escape_string($extract_id)."', 'full', '".pg_escape_string(getmypid())."');");

	runq("INSERT INTO extract_full (extract_id, source_path, source_timestamp, source_md5) VALUES ('".pg_escape_string($extract_id)."', '".pg_escape_string($source_path)."', '".pg_escape_string($source_timestamp)."', '".pg_escape_string($source_md5)."');");
} catch (Exception $e) {
	die("could not create process in database");
}



$extractdir = Conf::$software_path."extract/extract_processes/{$extract_id}";
$extractuntardir = Conf::$software_path."extract/extract_processes/{$extract_id}/untar";

var_dump($extractdir);

if (!mkdir($extractdir)) {
	die("could not create dump folder");
}

if (!mkdir($extractuntardir)) {
	die("could not create dump untar folder");
}

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

function execute_scp($source_path, $extractuntardir) {
	shell_exec("scp -i ".Conf::$identity." ".Conf::$server.":{$source_path} {$extractuntardir} > /etc/uniformetl/logs/extractlog");
}
function monitor_scp($source_path, $extractuntardir) {
	clearstatcache();
	echo floor(filesize($extractuntardir."/".basename($source_path)) / (1024 * 1024))."M\n";
}

doandmonitor(array("execute_scp", array($source_path, $extractuntardir)), array("monitor_scp", array($source_path, $extractuntardir)));


/* shell_exec("scp -i ".Conf::$identity." ".Conf::$server.":{$source_path} {$extractuntardir} > /etc/uniformetl/logs/extractlog"); */
/*
if (false) {
	die("could not get specified dump");
}
*/

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

$get_columns = New ExtractFullGetColumns;
$table_columns = $get_columns->start($extractuntardir."/taboutUserTableColumns.dat", $models->sources);
print_r($table_columns);

foreach ($models->sources as $i => $source) {
	$table = str_replace("%{extract_id}", $extract_id, $models->tables[$i]);
	echo "CREATE TABLE {$table} (\n  ".implode(" TEXT,\n  ", $table_columns[$source])." TEXT\n);\n\n";

	echo "COPY {$table} (".implode(", ", $table_columns[$source]).") FROM...\n\n";
}

function execute_group_sed($extractuntardir) {
	shell_exec("sed -n '/^[^|]*||[^|]*||\ *6052\ *||/p' {$extractuntardir}/taboutGroupMember.dat > {$extractuntardir}/taboutGroupMember.dat.tmp");
	shell_exec("mv {$extractuntardir}/taboutGroupMember.dat.tmp {$extractuntardir}/taboutGroupMember.dat");
}
function monitor_group_sed($extractuntardir) {
	clearstatcache();
	echo floor(filesize($extractuntardir."/taboutGroupMember.dat.tmp") / (1024 * 1024))."M\n";
}

if (in_array("GroupMember", $models->sources)) {
	doandmonitor(array("execute_group_sed", array($extractuntardir)), array("monitor_group_sed", array($extractuntardir)));
}

function execute_sed($extractuntardir, $sources) {
	foreach ($sources as $i => $source) {
		if ($source == "Customer") {
			shell_exec("sed -e '2~1s/^[0-9][0-9]\+\ *||\ *\(IEA\|TS[0-9][0-9]\)\ *||/~~&/g' -e 's/||/~~||~~/g' -e 's/*$%#\r/~~/g' -i {$extractuntardir}/tabout{$source}.dat");
	
		} else if ($source == "Invoice") {
			shell_exec("sed -e '2~1s/^\(IEA\|TS[0-9][0-9]\)\ *||/~~&/g' -e 's/||/~~||~~/g' -e 's/*$%#\r/~~/g' -i {$extractuntardir}/tabout{$source}.dat");
	
		} else if ($source == "Receipt") {
			shell_exec("sed -e '2~1s/^\(IEA\|TS[0-9][0-9]\)\ *||/~~&/g' -e 's/||/~~||~~/g' -e 's/*$%#\r/~~/g' -i {$extractuntardir}/tabout{$source}.dat");
	
		} else {
			shell_exec("sed -e '2~1s/^[0-9][0-9]\+\ *||/~~&/g' -e 's/||/~~||~~/g' -e 's/*$%#\r/~~/g' -i {$extractuntardir}/tabout{$source}.dat");
		}


/*
		mv $origin $destination
	
		sed -e '1s/^/~~/' -e '$d' -e 's/\\/\\\\/g' -e "s/'/\\\'/g" -e 's/||/|/g' -e "s/~~/'/g" $origin > $destinationtmp
	
		tr -d '\0' < $destinationtmp > $destinationtmp2
	
		mv $destinationtmp2 $destinationtmp
	
		perl -MEncode -ne 'binmode(STDOUT, ":utf8"); print decode("iso-8859-1", "$_");' < $destinationtmp > $destination
*/
	}
}
function monitor_sed($extractuntardir, $sources) {
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

doandmonitor(array("execute_sed", array($extractuntardir, $models->sources)), array("monitor_sed", array($extractuntardir, $models->sources)));

?>