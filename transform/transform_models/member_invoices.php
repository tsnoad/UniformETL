<?php

Class MemberInvoices {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array(
			"dump_%{extract_id}_invoice" => "Invoice"
		);
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}
	function hook_extract_index_sql($data) {
		return array(
			"CREATE INDEX dump_%{extract_id}_invoice_customerid ON dump_%{extract_id}_invoice (cast(customerid AS BIGINT));",
			"CREATE INDEX dump_%{extract_id}_invoice_batchid ON dump_%{extract_id}_invoice (cast(batchid AS BIGINT));",
			"CREATE INDEX dump_%{extract_id}_invoice_batchposition ON dump_%{extract_id}_invoice (cast(batchposition AS BIGINT));",
			"CREATE INDEX dump_%{extract_id}_invoice_datecreated ON dump_%{extract_id}_invoice (cast(to_timestamp(datecreated, 'Mon DD YYYY HH:MI:SS:MSPM') as timestamp));"
		);
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
			$name['batchid'] = trim($member_names_query_tmp['batchid']);
			$name['batchposition'] = trim($member_names_query_tmp['batchposition']);
			$name['type'] = trim($member_names_query_tmp['type']);
			$name['status'] = trim($member_names_query_tmp['status']);
			$name['amount'] = (float)trim($member_names_query_tmp['amount']);

			$name_hash = md5(implode("", $name));

			$members_names[$member_id][$name_hash] = $name;
		}

		return $members_names;
	}

	function get_src_members_names($chunk_id, $extract_id) {
		$src_member_names_query = runq("SELECT DISTINCT i.customerid as member_id, i.batchid::BIGINT as batchid, i.batchposition::BIGINT as batchposition, i.invoicetypeid as type, i.invoicestatusid as status, i.amount as amount FROM dump_{$extract_id}_invoice i INNER JOIN chunk_member_ids ch ON (ch.member_id=i.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}' "/* ."AND cast(to_timestamp(i.datecreated, 'Mon DD YYYY HH:MI:SS:MSPM') as timestamp)>now()-interval'24 months'" */.";");

		return $this->get_members_names($src_member_names_query);
	}

	function get_dst_members_names($chunk_id) {
		$dst_member_names_query = runq("SELECT DISTINCT i.member_id, i.batchid, i.batchposition, i.type, i.status, i.amount FROM invoices i INNER JOIN chunk_member_ids ch ON (ch.member_id=i.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_names($dst_member_names_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO invoices (member_id, batchid, batchposition, type, status, amount) VALUES ('".db_escape($data_add_item['member_id'])."', '".db_escape($data_add_item['batchid'])."', '".db_escape($data_add_item['batchposition'])."', '".db_escape($data_add_item['type'])."', '".db_escape($data_add_item['status'])."', '".db_escape($data_add_item['amount'])."');");
	}

	function update_data($data_update_item) {
		//Plural: Does no updating.
	}

	function delete_data($data_delete_by_member) {
		if (empty($data_delete_by_member)) return;

		foreach ($data_delete_by_member as $data_delete_item) {
			runq("DELETE FROM invoices WHERE member_id='".db_escape($data_delete_item['member_id'])."' AND batchid='".db_escape($data_delete_item['batchid'])."' AND batchposition='".db_escape($data_delete_item['batchposition'])."' AND type='".db_escape($data_delete_item['type'])."' AND status='".db_escape($data_delete_item['status'])."' AND ".db_choose(db_pgsql("amount='".db_escape($data_delete_item['amount'])."'"), db_mysql("round(amount, 6)=round('".db_escape($data_delete_item['amount'])."', 6)")).";");
		}
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New PluralTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}
}

?>