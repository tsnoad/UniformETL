<?php

require_once("/etc/uniformetl/autoload.php");

Class ExtractFullStagedPlugins {
	function hook_transform_deleted_members_query($data) {
		list($deleted_members_query, $extract_process) = $data;

		if ($extract_process['extractor'] != "full_staged") {
			return $data;
		}

		if (empty($deleted_members_query)) {
			return $data;
		}

		$latest_members_query = runq("SELECT * FROM extract_processes e INNER JOIN extract_latest_staged el ON (el.extract_id=e.extract_id) WHERE e.finished=TRUE AND e.finish_date>'".date("Y-m-d H:i:s", strtotime("-24hours", strtotime($extract_process['source_timestamp'])))."';");

		if (empty($latest_members_query)) {
			return $data;
		}

		$latest_members = array();

		if (!empty($latest_members_query)) {
			foreach ($latest_members_query as $latest_members_tmp) {
				foreach (json_decode($latest_members_tmp['member_ids']) as $latest_member_id) {
					$latest_member_ids[] = $latest_member_id;
				}
			}
		}

		if (!empty($deleted_members_query)) {
			foreach ($deleted_members_query as $deleted_member_key => $deleted_member_tmp) {
				if (in_array($deleted_member_tmp['member_id'], $latest_member_ids)) {
					unset($deleted_members_query[$deleted_member_key]);
					echo "Latest Memeber: {$deleted_member_tmp['member_id']}\n";
				}
			}
		}

		return array($deleted_members_query, $extract_process);
	}
}

?>