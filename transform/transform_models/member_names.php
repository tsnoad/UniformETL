<?php

Class MemberNames {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_%{extract_id}_name" => "Name");
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}
	function hook_extract_index_sql($data) {
		return array("CREATE INDEX dump_%{extract_id}_name_customerid ON dump_%{extract_id}_name (cast(customerid AS BIGINT));");
	}

	function get_src_data($src_member_ids_chunk, $extract_id) {
		return $this->get_src_members_names($src_member_ids_chunk, $extract_id);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_names($src_member_ids_chunk);
	}

	function get_members_names($member_names_query) {
		if (empty($member_names_query)) return null;

		foreach ($member_names_query as $member_names_query_tmp) {
			$member_id = trim($member_names_query_tmp['member_id']);

			$name['member_id'] = $member_id;
			$name['type'] = trim($member_names_query_tmp['type']);
			$name['given_names'] = trim($member_names_query_tmp['given_names']);
			$name['family_name'] = trim($member_names_query_tmp['family_name']);

			$name_hash = md5(implode("", $name));

			$members_names[$member_id][$name_hash] = $name;
		}

		return $members_names;
	}

	function get_src_members_names($chunk_id, $extract_id) {
		$src_member_names_query = runq("SELECT DISTINCT n.customerid as member_id, n.nametypeid as type, split_part(n.nameline2, ' ', 1) AS given_names, n.nameline1 as family_name FROM dump_{$extract_id}_name n INNER JOIN chunk_member_ids ch ON (ch.member_id=n.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_names($src_member_names_query);
	}

	function get_dst_members_names($chunk_id) {
		$dst_member_names_query = runq("SELECT DISTINCT n.member_id, n.type, n.given_names, n.family_name FROM names n INNER JOIN chunk_member_ids ch ON (ch.member_id=n.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_names($dst_member_names_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO names (member_id, type, given_names, family_name) VALUES ('".pg_escape_string($data_add_item['member_id'])."', '".pg_escape_string($data_add_item['type'])."', '".pg_escape_string($data_add_item['given_names'])."', '".pg_escape_string($data_add_item['family_name'])."');");
	}

	function update_data($data_update_item) {
		//needs to be coded
	}

	function delete_data($data_delete_by_member) {
		if (empty($data_delete_by_member)) return;

		foreach ($data_delete_by_member as $data_delete_item) {
			runq("DELETE FROM names WHERE member_id='".pg_escape_string($data_delete_item['member_id'])."' AND type='".pg_escape_string($data_delete_item['type'])."' AND given_names='".pg_escape_string($data_delete_item['given_names'])."' AND family_name='".pg_escape_string($data_delete_item['family_name'])."';");
		}
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New PluralTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member_plurals($data) {
		list($member_id) = $data;

		$names_query = runq("SELECT type, given_names, family_name FROM names n WHERE n.member_id='".pg_escape_string($member_id)."';");
		foreach ($names_query as $names_query_tmp) {
			//organise names by name type (there's only ever one name per name type)
			$user['names'][$names_query_tmp['type']] = array("given_names" => $names_query_tmp['given_names'], "family_name" => $names_query_tmp['family_name']);
		}

		return $user;
	}
}

?>