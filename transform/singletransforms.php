<?php

require_once("/etc/uniformetl/autoload.php");

Class SingleTransforms {
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
	
				if (!empty($src_data_member) && is_array($src_data_member) && array_keys($src_data_member) == array("member_id", "password", "salt", "hash")) {
					if (empty($dst_data_member)) {
						$data_add[] = $src_data_member;
					} else if ($dst_data_member['hash'] != md5($dst_data_member['salt'].$src_data_member['password'])) {
						$data_update[] = $src_data_member;
					} else {
						$data_nochange[] = $src_data_member;
					}
				} else {
					if (empty($dst_data_member)) {
						$data_add[] = $src_data_member;
					} else if ($dst_data_member != $src_data_member) {
						$data_update[] = $src_data_member;
					} else {
						$data_nochange[] = $src_data_member;
					}
				}
	
				unset($data_delete[$member_id]);
			}
		}

		$data_delete_count = count($data_delete);

		return array($data_add, $data_nochange, $data_update, $data_delete, $data_delete_count);
	}
}

?>