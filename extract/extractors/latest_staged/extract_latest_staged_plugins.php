<?php

require_once("/etc/uniformetl/autoload.php");

Class ExtractLatestStagedPlugins {
	function hook_transform_deleted_members_query($data) {
		list($deleted_members_query, $extract_process) = $data;

		if ($extract_process['extractor'] != "latest_staged") {
			return $data;
		}

		if (empty($deleted_members_query)) {
			return $data;
		}

		$deleted_members_query = array();

		return array($deleted_members_query, $extract_process);
	}
}

?>