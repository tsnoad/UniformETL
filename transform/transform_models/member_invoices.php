<?php

Class MemberInvoices {
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
		$src_member_names_query = runq("SELECT DISTINCT i.customerid as member_id, md5(trim(i.batchid::TEXT)||trim(i.batchposition::TEXT)) as batch_hash, i.invoicetypeid as type, i.invoicestatusid as status, i.amount as amount FROM dump_invoice i INNER JOIN chunk_member_ids ch ON (ch.member_id=i.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_names($src_member_names_query);
	}

	function get_dst_members_names($chunk_id) {
		$dst_member_names_query = runq("SELECT DISTINCT i.member_id, i.batch_hash, i.type, i.status, i.amount FROM invoices i INNER JOIN chunk_member_ids ch ON (ch.member_id=i.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_names($dst_member_names_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO invoices (member_id, batch_hash, type, status, amount) VALUES ('".pg_escape_string($data_add_item['member_id'])."', '".pg_escape_string($data_add_item['batch_hash'])."', '".pg_escape_string($data_add_item['type'])."', '".pg_escape_string($data_add_item['status'])."', '".pg_escape_string($data_add_item['amount'])."');");
	}

	function delete_data($data_delete_by_member) {
		if (empty($data_delete_by_member)) return;

		foreach ($data_delete_by_member as $data_delete_item) {
			runq("DELETE FROM invoices WHERE member_id='".pg_escape_string($data_delete_item['member_id'])."' AND batch_hash='".pg_escape_string($data_delete_item['batch_hash'])."' AND type='".pg_escape_string($data_delete_item['type'])."' AND status='".pg_escape_string($data_delete_item['status'])."' AND amount='".pg_escape_string($data_delete_item['amount'])."';");
		}
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New PluralTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}
}

?>