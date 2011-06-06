<?php

Class MemberDivisions {
	function get_src_data($src_member_ids_chunk) {
		return $this->get_src_members_grades($src_member_ids_chunk);
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

	function get_src_members_grades($chunk_id) {
		$src_member_passwords_query = runq("SELECT DISTINCT d.customerid as member_id, d.divisionid as division FROM dump_cpgcustomer d INNER JOIN chunk_member_ids ch ON (ch.member_id=d.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_grades($src_member_passwords_query);
	}

	function get_dst_members_grades($chunk_id) {
		$dst_member_passwords_query = runq("SELECT DISTINCT d.member_id, d.division FROM divisions d INNER JOIN chunk_member_ids ch ON (ch.member_id=d.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_grades($dst_member_passwords_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO divisions (member_id, division) VALUES ('".pg_escape_string($data_add_item['member_id'])."', '".pg_escape_string($data_add_item['division'])."');");
	}

	function delete_data($data_delete_item) {
	}

	function update_data($data_add_item) {
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member($data) {
		return array("d.division", "LEFT JOIN divisions d ON (d.member_id=m.member_id)");
	}
}

?>