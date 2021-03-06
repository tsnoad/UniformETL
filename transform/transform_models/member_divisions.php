<?php

Class MemberDivisions {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_%{extract_id}_cpgcustomer" => "cpgCustomer");
	}
	function hook_models_required_columns($data) {
		return array("cpgCustomer" => array("customerid", "cpgid", "custstatusid", "divisionid"));
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
			),
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_cpgcustomer_custstatusid ON dump_%{extract_id}_cpgcustomer (custstatusid) WHERE (custstatusid='MEMB');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_cpgcustomer MODIFY COLUMN custstatusid VARCHAR(32); CREATE INDEX dump_%{extract_id}_cpgcustomer_custstatusid ON dump_%{extract_id}_cpgcustomer (custstatusid);")
			)
		);
	}

	function get_src_data($src_member_ids_chunk, $extract_id) {
		return $this->get_src_members_grades($src_member_ids_chunk, $extract_id);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_grades($src_member_ids_chunk);
	}

	function get_members_grades($member_passwords_query) {
		if (empty($member_passwords_query)) return null;

		foreach ($member_passwords_query as $member_passwords_query_tmp) {
			$member_id = trim($member_passwords_query_tmp['member_id']);

			$password['member_id'] = $member_id;
			$password['division'] = trim($member_passwords_query_tmp['division']);

			$members_passwords[$member_id] = $password;
		}

		return $members_passwords;
	}

	function get_src_members_grades($chunk_id, $extract_id) {
		$src_member_passwords_query = runq("SELECT DISTINCT d.customerid as member_id, d.divisionid as division FROM dump_{$extract_id}_cpgcustomer d INNER JOIN chunk_member_ids ch ON (ch.member_id=".db_cast_bigint("d.customerid").") WHERE ch.chunk_id='{$chunk_id}' AND d.cpgid='IEA';");

		return $this->get_members_grades($src_member_passwords_query);
	}

	function get_dst_members_grades($chunk_id) {
		$dst_member_passwords_query = runq("SELECT DISTINCT d.member_id, d.division FROM divisions d INNER JOIN chunk_member_ids ch ON (ch.member_id=d.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_grades($dst_member_passwords_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO divisions (member_id, division) VALUES ('".db_escape($data_add_item['member_id'])."', '".db_escape($data_add_item['division'])."');");
	}

	function delete_data($data_delete_item) {
		runq("DELETE FROM divisions WHERE member_id='".db_escape($data_delete_item['member_id'])."' AND division='".db_escape($data_delete_item['division'])."';");
	}

	function update_data($data_update_item) {
		runq("UPDATE divisions SET division='".db_escape($data_update_item['division'])."' WHERE member_id='".db_escape($data_update_item['member_id'])."';");
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member($data) {
		return array("dn.name as division", "LEFT JOIN divisions d ON (d.member_id=m.member_id) LEFT JOIN division_names dn ON (dn.division=d.division)");
	}
}

?>