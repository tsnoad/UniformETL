<?php

Class ExtractLatestPlugins {
	function hook_transform_deleted_members_query($data) {
		list($deleted_members_query, $transform_process_id, $extract_process) = $data;

		if ($extract_process['extractor'] != "latest") {
			return $data;
		}

		$deleted_members_query = array();
var_dump("ohai");

		return array($deleted_members_query, $transform_process_id, $extract_process);
	}
}

?>