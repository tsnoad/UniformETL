<?php

Class MemberEcpdStatuses {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_%{extract_id}_groupmember" => "GroupMember");
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}
	function hook_extract_index_sql($data) {
		return array(
			"CREATE INDEX dump_%{extract_id}_groupmember_groupid ON dump_%{extract_id}_groupmember (groupid) WHERE (groupid='6052');",
			"CREATE INDEX dump_%{extract_id}_groupmember_customerid ON dump_%{extract_id}_groupmember (cast(customerid AS BIGINT)) WHERE (groupid='6052');"
		);
	}

	function get_src_data($src_member_ids_chunk, $extract_id) {
		return $this->get_src_members_ecpd_statuses($src_member_ids_chunk, $extract_id);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_ecpd_statuses($src_member_ids_chunk);
	}

	function get_members_ecpd_statuses($member_ecpd_statuses_query) {
		if (empty($member_ecpd_statuses_query)) return null;

		foreach ($member_ecpd_statuses_query as $member_ecpd_statuses_query_tmp) {
			$member_id = trim($member_ecpd_statuses_query_tmp['member_id']);

			$members_ecpd_statuses[$member_id] = $member_id;
		}

		return $members_ecpd_statuses;
	}

	function get_src_members_ecpd_statuses($chunk_id, $extract_id) {
		$src_member_ecpd_statuses_query = runq("SELECT DISTINCT g.customerid as member_id FROM dump_{$extract_id}_groupmember g INNER JOIN chunk_member_ids ch ON (ch.member_id=g.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}' AND g.groupid='6052';");

		return $this->get_members_ecpd_statuses($src_member_ecpd_statuses_query);
	}

	function get_dst_members_ecpd_statuses($chunk_id) {
		$dst_member_ecpd_statuses_query = runq("SELECT DISTINCT e.member_id FROM ecpd_statuses e INNER JOIN chunk_member_ids ch ON (ch.member_id=e.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_ecpd_statuses($dst_member_ecpd_statuses_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO ecpd_statuses (member_id) VALUES ('".pg_escape_string($data_add_item)."');");
	}

	function update_data($data_update_item) {
		//needs to be coded
	}

	function delete_data($data_delete_item) {
		runq("DELETE FROM ecpd_statuses WHERE member_id='".pg_escape_string($data_delete_item)."';");
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member($data) {
		return array("e.member_id=m.member_id as ecpd_status", "LEFT JOIN ecpd_statuses e ON (e.member_id=m.member_id)");
	}
}

?>