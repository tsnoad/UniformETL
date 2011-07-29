<?php

Class MemberPersonals {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_%{extract_id}_customer" => "Customer");
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}
	function hook_extract_index_sql($data) {
		return array(
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_customer_customerid ON dump_%{extract_id}_customer (cast(customerid AS BIGINT));"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_customer MODIFY COLUMN customerid BIGINT; CREATE INDEX dump_%{extract_id}_customer_customerid ON dump_%{extract_id}_customer (customerid);")
			)
		);
	}

	function get_src_data($src_member_ids_chunk, $extract_id) {
		return $this->get_src_members_personals($src_member_ids_chunk, $extract_id);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_personals($src_member_ids_chunk);
	}

	function get_members_personals($member_personals_query) {
		if (empty($member_personals_query)) return null;

		foreach ($member_personals_query as $member_personals_query_tmp) {
			$member_id = trim($member_personals_query_tmp['member_id']);
			$personals['member_id'] = $member_id;
			$personals['gender'] = trim($member_personals_query_tmp['gender']);
			$personals['date_of_birth'] = trim($member_personals_query_tmp['date_of_birth']);

			$personals['gender'] = strtoupper($personals['gender']);

			if ($personals['gender'] != "M" && $personals['gender'] != "F" && $personals['gender'] != "O") {
				$personals['gender'] = "";
			}

			$members_personals[$member_id] = $personals;
		}

		return $members_personals;
	}

	function get_src_members_personals($chunk_id, $extract_id) {
		$select_dateofbirth = db_choose(db_pgsql("cast(to_timestamp(dob, 'Mon DD YYYY HH:MI:SS:MSPM') as timestamp)"), db_mysql("str_to_date(dob, '%b %e %Y %l:%i:%s:%f%p')"));

		$src_member_ecpd_statuses_query = runq("SELECT DISTINCT c.customerid AS member_id, c.sex AS gender, CASE WHEN dob IS NOT NULL AND dob!='' THEN {$select_dateofbirth} ELSE NULL END AS date_of_birth FROM dump_{$extract_id}_customer c INNER JOIN chunk_member_ids ch ON (ch.member_id=".db_cast_bigint("c.customerid").") WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_personals($src_member_ecpd_statuses_query);
	}

	function get_dst_members_personals($chunk_id) {
		$dst_member_ecpd_statuses_query = runq("SELECT DISTINCT p.member_id, p.gender, p.date_of_birth FROM personals p INNER JOIN chunk_member_ids ch ON (ch.member_id=p.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_personals($dst_member_ecpd_statuses_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO personals (member_id, gender, date_of_birth) VALUES ('".db_escape($data_add_item['member_id'])."', ".(!empty($data_add_item['gender']) ? "'".db_escape($data_add_item['gender'])."'" : "NULL").", ".(!empty($data_add_item['date_of_birth']) ? "'".db_escape($data_add_item['date_of_birth'])."'" : "NULL").");");
	}

	function update_data($data_update_item) {
		runq("UPDATE personals SET gender=".(!empty($data_update_item['gender']) ? "'".db_escape($data_update_item['gender'])."'" : "NULL").", date_of_birth=".(!empty($data_update_item['date_of_birth']) ? "'".db_escape($data_update_item['date_of_birth'])."'" : "NULL")." WHERE member_id='".db_escape($data_update_item['member_id'])."';");
	}

	function delete_data($data_delete_item) {
/* 		runq("DELETE FROM web_statuses WHERE member_id='".db_escape($data_delete_item)."';"); */
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member($data) {
		return array("p.gender, p.date_of_birth", "LEFT JOIN personals p ON (p.member_id=m.member_id)");
	}
}

?>