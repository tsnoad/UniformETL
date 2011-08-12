<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/autoload.php");

Class PluginHistory {
	function record_update($data) {
		list($transform, $data_update_item) = $data;

		$history = array("add", $transform, $data_update_item);

		runq("INSERT INTO history (data) VALUES ('".pg_escape_string(json_encode($history))."')");
	}
}

?>