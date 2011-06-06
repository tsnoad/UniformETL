<?php

Class MemberIds {
	function get_src_data($src_member_ids_chunk) {
		return $this->get_src_members($src_member_ids_chunk);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members($src_member_ids_chunk);
	}

	function get_members($member_query) {
		if (empty($member_query)) return null;

		foreach ($member_query as $member_query_tmp) {
			$member_id = trim($member_query_tmp['member_id']);
			$members[$member_id] = $member_id;
		}

		return $members;
	}

	function get_src_members($chunk_id) {
		$src_member_query = runq("SELECT DISTINCT c.customerid AS member_id FROM dump_cpgcustomer c INNER JOIN chunk_member_ids ch ON (ch.member_id=c.customerid::BIGINT) WHERE c.cpgid='IEA' AND ch.chunk_id='{$chunk_id}';");

		return $this->get_members($src_member_query);
	}

	function get_dst_members($chunk_id) {
		$dst_member_query = runq("SELECT DISTINCT m.member_id FROM member_ids m INNER JOIN chunk_member_ids ch ON (ch.member_id=m.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members($dst_member_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO member_ids (member_id) VALUES ('".pg_escape_string($data_add_item)."');");
	}

	function update_data($data_update_item) {
		//needs to be coded
	}

	function delete_data($data_delete_item) {
/* 		runq("DELETE FROM member_ids WHERE member_id='".pg_escape_string($data_delete_item)."';"); */
	}

	function transform($src_members, $dst_members) {
		$members_add = array();
		$members_update = array();
		$members_delete = $dst_members;
		$data_delete_count = 0;

		foreach ($src_members as $member_id => $src_member) {
			$dst_member = $dst_members[$member_id];

			if (empty($dst_member)) {
				$members_add[$member_id] = $member_id;
			} else if ($dst_member != $src_member) {
				$data_update[] = $src_data_member;
			} else {
/* 				$members_update[$member_id] = $member_id; */
			}

			unset($members_delete[$member_id]);
		}

		$data_delete_count = count($data_delete);

		return array($members_add, $members_update, $members_delete, $data_delete_count);
	}

	function hook_api_get_member($data) {
		return array("m.member_id", "member_ids m");
	}
}

?>