<?php

Class ExtractFullPlugins {
	function hook_transform_deleted_members_query($data) {
		list($deleted_members_query, $transform_process_id, $extract_process) = $data;

		if ($extract_process['extractor'] != "full") {
			return $data;
		}

		if (empty($deleted_members_query)) {
			return $data;
		}

		$latest_members_query = runq("SELECT * FROM extract_processes e INNER JOIN extract_latest el ON (el.process_id=e.process_id) WHERE e.finished=TRUE AND e.finish_date>'".date("Y-m-d H:i:s", strtotime("-24hours", strtotime($extract_process['source_timestamp'])))."';");

		if (empty($latest_members_query)) {
			return $data;
		}

		$latest_members = array();

		foreach ($latest_members_query as $latest_members_tmp) {
			foreach (explode(",", trim($latest_members_tmp['member_ids'], "{}")) as $latest_member_id) {
				$latest_member_ids[] = $latest_member_id;
			}
		}

		foreach ($deleted_members_query as $deleted_member_key => $deleted_member_tmp) {
			if (in_array($deleted_member_tmp['member_id'], $latest_member_ids)) {
				unset($deleted_members_query[$deleted_member_key]);
				echo "Latest Memeber: {$deleted_member_tmp['member_id']}\n";
			}
		}

		return array($deleted_members_query, $transform_process_id, $extract_process);
	}
}

?>