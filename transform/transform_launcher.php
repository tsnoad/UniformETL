#!/usr/bin/php5
<?php

require("/etc/uniformetl/config.php");
require("/etc/uniformetl/database.php");

class Launcher {
	public $conf;

	function start() {
		$this->conf = New Conf;

		$candidates = runq("SELECT p.process_id FROM processes p INNER JOIN extract_processes ep ON (ep.process_id=p.process_id) LEFT OUTER JOIN transform_processes tp ON (tp.process_id=p.process_id) WHERE ep.finished=TRUE AND ep.failed=FALSE AND tp.process_id IS NULL;");

		foreach ($candidates as $transform) {
			shell_exec($this->conf->software_path."transform/transform.php ".escapeshellarg($transform['process_id'])." > ".$this->conf->software_path."logs/transformlog 2>".$this->conf->software_path."logs/transformlog & echo $!");
			die("transform started");
		}
	}
}

$launcher = New Launcher;
$launcher->start();

?>