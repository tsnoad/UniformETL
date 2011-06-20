<?php

Class MemberReceipts {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_receipt" => "Receipt");
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}

	function get_src_data($src_member_ids_chunk) {
		return $this->get_src_members_names($src_member_ids_chunk);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_names($src_member_ids_chunk);
	}

	function get_members_names($member_names_query) {
		if (empty($member_names_query)) return null;

		foreach ($member_names_query as $member_names_query_tmp) {
			$member_id = trim($member_names_query_tmp['member_id']);

			$name['member_id'] = $member_id;
			$name['batch_hash'] = trim($member_names_query_tmp['batch_hash']);
			$name['type'] = trim($member_names_query_tmp['type']);
			$name['status'] = trim($member_names_query_tmp['status']);
			$name['amount'] = (float)trim($member_names_query_tmp['amount']);

			$name_hash = md5(implode("", $name));

			$members_names[$member_id][$name_hash] = $name;
		}

		return $members_names;
	}

	function get_src_members_names($chunk_id) {
		$src_member_names_query = runq("SELECT DISTINCT r.customerid as member_id, md5(trim(r.batchid::TEXT)||trim(r.batchposition::TEXT)) as batch_hash, r.receipttypeid as type, r.receiptstatusid as status, r.amount as amount FROM dump_receipt r INNER JOIN chunk_member_ids ch ON (ch.member_id=r.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}' AND r.customerid NOT ILIKE '%+%';");

		return $this->get_members_names($src_member_names_query);
	}

	function get_dst_members_names($chunk_id) {
		$dst_member_names_query = runq("SELECT DISTINCT r.member_id, r.batch_hash, r.type, r.status, r.amount FROM receipts r INNER JOIN chunk_member_ids ch ON (ch.member_id=r.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_names($dst_member_names_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO receipts (member_id, batch_hash, type, status, amount) VALUES ('".pg_escape_string($data_add_item['member_id'])."', '".pg_escape_string($data_add_item['batch_hash'])."', '".pg_escape_string($data_add_item['type'])."', '".pg_escape_string($data_add_item['status'])."', '".pg_escape_string($data_add_item['amount'])."');");
	}

	function update_data($data_update_item) {
		//needs to be coded
	}

	function delete_data($data_delete_by_member) {
		if (empty($data_delete_by_member)) return;

		foreach ($data_delete_by_member as $data_delete_item) {
			runq("DELETE FROM receipts WHERE member_id='".pg_escape_string($data_delete_item['member_id'])."' AND batch_hash='".pg_escape_string($data_delete_item['batch_hash'])."' AND type='".pg_escape_string($data_delete_item['type'])."' AND status='".pg_escape_string($data_delete_item['status'])."' AND type='".pg_escape_string($data_delete_item['type'])."';");
		}
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New PluralTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}
}

?>