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
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_groupmember_groupid ON dump_%{extract_id}_groupmember (groupid) WHERE (groupid='6052');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_groupmember MODIFY COLUMN groupid BIGINT; CREATE INDEX dump_%{extract_id}_groupmember_groupid ON dump_%{extract_id}_groupmember (groupid);")
			),
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_groupmember_customerid ON dump_%{extract_id}_groupmember (cast(customerid AS BIGINT)) WHERE (groupid='6052');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_groupmember MODIFY COLUMN customerid BIGINT; CREATE INDEX dump_%{extract_id}_groupmember_customerid ON dump_%{extract_id}_address (customerid);")
			)
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
			$status['member_id'] = $member_id;

			$status['participant'] = trim($member_ecpd_statuses_query_tmp['participant']);
			$status['coordinator'] = trim($member_ecpd_statuses_query_tmp['coordinator']);

			$members_ecpd_statuses[$member_id] = $status;
		}

		return $members_ecpd_statuses;
	}

	function get_src_members_ecpd_statuses($chunk_id, $extract_id) {
/* 		$src_member_ecpd_statuses_query = runq("SELECT DISTINCT g.customerid as member_id FROM dump_{$extract_id}_groupmember g INNER JOIN chunk_member_ids ch ON (ch.member_id=g.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}' AND g.groupid='6052';"); */

		$src_member_ecpd_statuses_query = runq("SELECT DISTINCT ch.member_id, CASE WHEN gp.groupid='6052' THEN TRUE ELSE FALSE END as participant, CASE WHEN gc.subgroupid='28507' THEN TRUE ELSE FALSE END as coordinator FROM chunk_member_ids ch LEFT OUTER JOIN dump_{$extract_id}_groupmember gp ON (gp.customerid::BIGINT=ch.member_id AND gp.groupid='6052') LEFT OUTER JOIN dump_{$extract_id}_groupmember gc ON (gc.customerid::BIGINT=ch.member_id AND gc.subgroupid='28507') WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_ecpd_statuses($src_member_ecpd_statuses_query);
	}

	function get_dst_members_ecpd_statuses($chunk_id) {
		$dst_member_ecpd_statuses_query = runq("SELECT DISTINCT e.member_id, e.participant, e.coordinator FROM ecpd_statuses e INNER JOIN chunk_member_ids ch ON (ch.member_id=e.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_ecpd_statuses($dst_member_ecpd_statuses_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO ecpd_statuses (member_id, participant, coordinator) VALUES ('".pg_escape_string($data_add_item['member_id'])."', '".db_boolean($data_add_item['participant'])."', '".db_boolean($data_add_item['coordinator'])."');");
	}

	function update_data($data_update_item) {
		//needs to be coded
	}

	function delete_data($data_delete_item) {
/* 		runq("DELETE FROM ecpd_statuses WHERE member_id='".pg_escape_string($data_delete_item['member_id'])."';"); */
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member($data) {
		return array("ecpd.participant as ecpd_particpant, ecpd.coordinator as ecpd_coordinator", "LEFT JOIN epdp_statuses ecpd ON (ecpd.member_id=m.member_id)");
	}
}

?>