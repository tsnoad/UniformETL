<?php

Class MemberStatuses {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_%{extract_id}_cpgcustomer" => "cpgCustomer");
	}
	function hook_models_required_columns($data) {
		return array("cpgCustomer" => array("customerid", "cpgid", "custstatusid", "finstatus"));
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}
	function hook_extract_index_sql($data) {
		return array(
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_cpgcustomer_cpgid ON dump_%{extract_id}_cpgcustomer (cpgid) WHERE (cpgid='IEA');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_cpgcustomer MODIFY COLUMN cpgid VARCHAR(32); CREATE INDEX dump_%{extract_id}_cpgcustomer_cpgid ON dump_%{extract_id}_cpgcustomer (cpgid);")
			),
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_cpgcustomer_customerid ON dump_%{extract_id}_cpgcustomer (cast(customerid AS BIGINT)) WHERE (cpgid='IEA');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_cpgcustomer MODIFY COLUMN customerid BIGINT; CREATE INDEX dump_%{extract_id}_cpgcustomer_customerid ON dump_%{extract_id}_cpgcustomer (customerid);")
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

			$status['member'] = trim($member_ecpd_statuses_query_tmp['member']);
			$status['financial'] = trim($member_ecpd_statuses_query_tmp['financial']);

			$members_ecpd_statuses[$member_id] = $status;
		}

		return $members_ecpd_statuses;
	}

	function get_src_members_ecpd_statuses($chunk_id, $extract_id) {
		$src_member_ecpd_statuses_query = runq("SELECT DISTINCT ".db_cast_bigint("c.customerid")." as member_id, custstatusid='MEMB' as member, finstatus='1' as financial FROM dump_{$extract_id}_cpgcustomer c INNER JOIN chunk_member_ids ch ON (ch.member_id=".db_cast_bigint("c.customerid").") WHERE ch.chunk_id='{$chunk_id}' AND c.cpgid='IEA';");

		return $this->get_members_ecpd_statuses($src_member_ecpd_statuses_query);
	}

	function get_dst_members_ecpd_statuses($chunk_id) {
		$dst_member_ecpd_statuses_query = runq("SELECT DISTINCT e.member_id, e.member, e.financial FROM statuses e INNER JOIN chunk_member_ids ch ON (ch.member_id=e.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_ecpd_statuses($dst_member_ecpd_statuses_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO statuses (member_id, member, financial) VALUES ('".db_escape($data_add_item['member_id'])."', '".db_boolean($data_add_item['member'])."', '".db_boolean($data_add_item['financial'])."');");
	}

	function update_data($data_update_item) {
		runq("UPDATE statuses SET member='".db_boolean($data_update_item['member'])."', financial='".db_boolean($data_update_item['financial'])."' WHERE member_id='".db_escape($data_update_item['member_id'])."';");
	}

	function delete_data($data_delete_item) {
		runq("DELETE FROM statuses WHERE member_id='".db_escape($data_delete_item['member_id'])."' AND member='".db_boolean($data_delete_item['member'])."' AND financial='".db_boolean($data_delete_item['financial'])."';");
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member($data) {
		return array("stat.member as member", "LEFT JOIN statuses stat ON (stat.member_id=m.member_id)");
	}
}

?>