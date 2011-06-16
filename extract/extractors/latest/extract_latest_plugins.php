<?php

Class ExtractLatestPlugins {
	function hook_transform_deleted_members_query($data) {
		list($deleted_members_query, $transform_process_id, $extract_process) = $data;

		if ($extract_process['extractor'] != "latest") {
			return $data;
		}

		if (empty($deleted_members_query)) {
			return $data;
		}

		$deleted_members_query = array();

		return array($deleted_members_query, $transform_process_id, $extract_process);
	}
}

?>