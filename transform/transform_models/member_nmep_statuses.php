<?php

Class MemberNmepStatuses {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_%{extract_id}_groupmember" => "GroupMember");
	}
	function hook_models_required_columns($data) {
		return array("GroupMember" => array("customerid", "groupid", "subgroupid"));
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}
	function hook_extract_index_sql($data) {
		return array(
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_nmep_groupmember_groupid ON dump_%{extract_id}_groupmember (groupid) WHERE (groupid='10801');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_groupmember MODIFY COLUMN groupid BIGINT; CREATE INDEX dump_%{extract_id}_groupmember_groupid ON dump_%{extract_id}_groupmember (groupid);")
			),
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_groupmember_nmep_subgroupid ON dump_%{extract_id}_groupmember (subgroupid) WHERE (subgroupid='39685');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_groupmember MODIFY COLUMN subgroupid BIGINT; CREATE INDEX dump_%{extract_id}_groupmember_subgroupid ON dump_%{extract_id}_groupmember (subgroupid);")
			),
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_groupmember_nmep_customerid ON dump_%{extract_id}_groupmember (cast(customerid AS BIGINT)) WHERE (groupid='10801');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_groupmember MODIFY COLUMN customerid BIGINT; CREATE INDEX dump_%{extract_id}_groupmember_customerid ON dump_%{extract_id}_groupmember (customerid);")
			)
		);
	}

	function get_src_data($src_member_ids_chunk, $extract_id) {
		return $this->get_src_members_nmep_statuses($src_member_ids_chunk, $extract_id);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_nmep_statuses($src_member_ids_chunk);
	}

	function get_members_nmep_statuses($member_nmep_statuses_query) {
		if (empty($member_nmep_statuses_query)) return null;

		foreach ($member_nmep_statuses_query as $member_nmep_statuses_query_tmp) {
			$member_id = trim($member_nmep_statuses_query_tmp['member_id']);
			$status['member_id'] = $member_id;

			$status['participant'] = trim($member_nmep_statuses_query_tmp['participant']);
			$status['coordinator'] = trim($member_nmep_statuses_query_tmp['coordinator']);

			$members_nmep_statuses[$member_id] = $status;
		}

		return $members_nmep_statuses;
	}

	function get_src_members_nmep_statuses($chunk_id, $extract_id) {
		$src_member_nmep_statuses_query = runq("SELECT DISTINCT ch.member_id, CASE WHEN gp.groupid='6052' THEN TRUE ELSE FALSE END as participant FROM chunk_member_ids ch LEFT OUTER JOIN dump_{$extract_id}_groupmember gp ON (gp.customerid::BIGINT=ch.member_id AND gp.groupid='6052') WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_nmep_statuses($src_member_nmep_statuses_query);
	}

	function get_dst_members_nmep_statuses($chunk_id) {
		$dst_member_nmep_statuses_query = runq("SELECT DISTINCT e.member_id, e.participant FROM nmep_statuses e INNER JOIN chunk_member_ids ch ON (ch.member_id=e.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_nmep_statuses($dst_member_nmep_statuses_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO nmep_statuses (member_id, participant) VALUES ('".pg_escape_string($data_add_item['member_id'])."', '".db_boolean($data_add_item['participant'])."');");
	}

	function update_data($data_update_item) {
		runq("UPDATE nmep_statuses SET participant='".db_boolean($data_update_item['participant'])."' WHERE member_id='".db_escape($data_update_item['member_id'])."';");
	}

	function delete_data($data_delete_item) {
		runq("DELETE FROM nmep_statuses WHERE member_id='".db_escape($data_delete_item['member_id'])."' AND participant='".db_boolean($data_delete_item['participant'])."';");
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member($data) {
		return array("nmep.participant as nmep_participant", "LEFT JOIN nmep_statuses nmep ON (nmep.member_id=m.member_id)");
	}
}

?>