<?php

Class MemberGrades {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_cpgcustomer");
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}

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
			$password['grade'] = trim($member_passwords_query_tmp['grade']);

			$members_passwords[$member_id] = $password;
		}

		return $members_passwords;
	}

	function get_src_members_grades($chunk_id) {
		$src_member_passwords_query = runq("SELECT DISTINCT g.customerid as member_id, g.gradeid as grade FROM dump_cpgcustomer g INNER JOIN chunk_member_ids ch ON (ch.member_id=g.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}' AND g.cpgid='IEA';");

		return $this->get_members_grades($src_member_passwords_query);
	}

	function get_dst_members_grades($chunk_id) {
		$dst_member_passwords_query = runq("SELECT DISTINCT g.member_id, g.grade FROM grades g INNER JOIN chunk_member_ids ch ON (ch.member_id=g.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_grades($dst_member_passwords_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO grades (member_id, grade) VALUES ('".pg_escape_string($data_add_item['member_id'])."', '".pg_escape_string($data_add_item['grade'])."');");
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
		return array("g.grade", "LEFT JOIN grades g ON (g.member_id=m.member_id)");
	}
}

?>