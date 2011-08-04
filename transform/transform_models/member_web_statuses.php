<?php

Class MemberWebStatuses {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_%{extract_id}_cpgcustomer" => "cpgCustomer");
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
		return $this->get_src_members_web_statuses($src_member_ids_chunk, $extract_id);
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

	function get_src_members_web_statuses($chunk_id, $extract_id) {
		$src_member_ecpd_statuses_query = runq("SELECT DISTINCT ".db_cast_bigint("c.customerid")." as member_id FROM dump_{$extract_id}_cpgcustomer c INNER JOIN chunk_member_ids ch ON (ch.member_id=".db_cast_bigint("c.customerid").") WHERE ch.chunk_id='{$chunk_id}' AND c.cpgid='IEA' AND c.custstatusid='MEMB';");

		return $this->get_members_web_statuses($src_member_ecpd_statuses_query);
	}

	function get_dst_members_web_statuses($chunk_id) {
		$dst_member_ecpd_statuses_query = runq("SELECT DISTINCT w.member_id FROM web_statuses w INNER JOIN chunk_member_ids ch ON (ch.member_id=w.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_web_statuses($dst_member_ecpd_statuses_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO web_statuses (member_id) VALUES ('".db_escape($data_add_item)."');");
	}

	function update_data($data_update_item) {
		//needs to be coded
	}

	function delete_data($data_delete_item) {
		runq("DELETE FROM web_statuses WHERE member_id='".db_escape($data_delete_item)."';");
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member($data) {
		return array("w.member_id=m.member_id as web_status", "LEFT JOIN web_statuses w ON (w.member_id=m.member_id)");
	}
}

?>