<?php

Class MemberWebStatuses {
	function get_src_data($src_member_ids_chunk) {
		return $this->get_src_members_web_statuses($src_member_ids_chunk);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_web_statuses($src_member_ids_chunk);
	}

	function get_members_web_statuses($member_web_statuses_query) {
		if (empty($member_web_statuses_query)) return null;

		foreach ($member_web_statuses_query as $member_web_statuses_query_tmp) {
			$member_id = trim($member_web_statuses_query_tmp['member_id']);

			$members_web_statuses[$member_id] = $member_id;
		}

		return $members_web_statuses;
	}

	function get_src_members_web_statuses($chunk_id) {
		$src_member_ecpd_statuses_query = runq("SELECT DISTINCT c.customerid as member_id FROM dump_cpgcustomer c INNER JOIN chunk_member_ids ch ON (ch.member_id=c.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}' AND c.custstatusid='MEMB';");

		return $this->get_members_web_statuses($src_member_ecpd_statuses_query);
	}

	function get_dst_members_web_statuses($chunk_id) {
		$dst_member_ecpd_statuses_query = runq("SELECT DISTINCT w.member_id FROM web_statuses w INNER JOIN chunk_member_ids ch ON (ch.member_id=w.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_web_statuses($dst_member_ecpd_statuses_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO web_statuses (member_id) VALUES ('".pg_escape_string($data_add_item)."');");
	}

	function delete_data($data_delete_item) {
		runq("DELETE FROM web_statuses WHERE member_id='".pg_escape_string($data_delete_item)."';");
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}
}

?>