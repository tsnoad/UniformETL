<?php

Class PluralTransforms {
	function transform($src_data_by_members, $dst_data_by_members) {
/*
		if (empty($src_data_by_members)) {
			echo "no source data\n";
			return null;
		}
*/

		$data_add = array();
		$data_nochange = array();
		$data_update = array();
		$data_delete = $dst_data_by_members;
		$data_delete_count = 0;

		if (!empty($src_data_by_members)) {
			foreach ($src_data_by_members as $member_id => $src_data_member) {
				$dst_data_member = $dst_data_by_members[$member_id];
	
				$dst_data_member_hashes = array();
	
				if (!empty($dst_data_member)) {
					$dst_data_member_hashes = array_keys($dst_data_member);
				}
	
				foreach ($src_data_member as $name_hash => $src_data_member_data) {
					if (!in_array($name_hash, $dst_data_member_hashes)) {
						$data_add[] = $src_data_member_data;
					} else {
						$data_nochange[] = $src_data_member_data;
					}
	
					unset($data_delete[$member_id][$name_hash]);
				}
			}
		}

		if (!empty($data_delete)) {
			foreach ($data_delete as $member_id => $data_delete_tmp) {
				if (empty($data_delete_tmp)) {
					unset($data_delete[$member_id]);
				} else {
					$data_delete_count += count($data_delete_tmp);
				}
			}
		}

		return array($data_add, $data_nochange, $data_update, $data_delete, $data_delete_count);
	}
}

?>