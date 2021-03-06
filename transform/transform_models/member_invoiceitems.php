<?php

Class MemberInvoiceItems {
	function hook_models_required_transforms($data) {
		return array("MemberIds", "MemberInvoices");
	}
	function hook_models_required_tables($data) {
		return array(
			"dump_%{extract_id}_invoiceitem" => "InvoiceItem"
		);
	}
	function hook_models_required_columns($data) {
		return array();
	}
	function hook_models_transform_priority($data) {
		return "tertiary";
	}
	function hook_extract_index_sql($data) {
		return array(
			"CREATE INDEX dump_%{extract_id}_invoiceitem_batchid ON dump_%{extract_id}_invoiceitem (cast(batchid AS BIGINT));",
			"CREATE INDEX dump_%{extract_id}_invoiceitem_batchposition ON dump_%{extract_id}_invoiceitem (cast(batchposition AS BIGINT));",
			"CREATE INDEX dump_%{extract_id}_invoiceitem_itemcode ON dump_%{extract_id}_invoiceitem (itemcode);",
			"CREATE INDEX dump_%{extract_id}_invoiceitem_subitemcode ON dump_%{extract_id}_invoiceitem (subitemcode);"
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
			$name['invoice_id'] = trim($member_names_query_tmp['invoice_id']);
			$name['itemcode'] = trim($member_names_query_tmp['itemcode']);
			$name['subitemcode'] = trim($member_names_query_tmp['subitemcode']);
			$name['quantity'] = trim($member_names_query_tmp['quantity']);
			$name['unitamount'] = (float)trim($member_names_query_tmp['unitamount']);
			$name['amount'] = (float)trim($member_names_query_tmp['amount']);

			$name_hash = md5(implode("", $name));

			$members_names[$member_id][$name_hash] = $name;
		}

		return $members_names;
	}

	function get_src_members_names($chunk_id, $extract_id) {
		$src_member_names_query = runq("
SELECT DISTINCT 
i.member_id, 
i.id as invoice_id, 
ii.itemcode as itemcode,
ii.subitemcode as subitemcode,
ii.unitamount::FLOAT as unitamount, 
ii.quantity as quantity,
ii.unitamount::FLOAT * ii.quantity::FLOAT as amount
FROM invoices i 
INNER JOIN dump_{$extract_id}_invoiceitem ii ON (ii.batchid::BIGINT=i.batchid AND ii.batchposition::BIGINT=i.batchposition)
INNER JOIN chunk_member_ids ch ON (ch.member_id=i.member_id) 
WHERE ch.chunk_id='{$chunk_id}';
");

		return $this->get_members_names($src_member_names_query);
	}

	function get_dst_members_names($chunk_id) {
		$dst_member_names_query = runq("SELECT DISTINCT i.member_id, ii.invoice_id, ii.itemcode, ii.subitemcode, ii.quantity, ii.unitamount, ii.amount FROM invoices i INNER JOIN invoiceitems ii ON (ii.invoice_id=i.id) INNER JOIN chunk_member_ids ch ON (ch.member_id=i.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_names($dst_member_names_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO invoiceitems (invoice_id, itemcode, subitemcode, quantity, unitamount, amount) VALUES ('".pg_escape_string($data_add_item['invoice_id'])."', '".pg_escape_string($data_add_item['itemcode'])."', '".pg_escape_string($data_add_item['subitemcode'])."', '".pg_escape_string($data_add_item['quantity'])."', '".pg_escape_string($data_add_item['unitamount'])."', '".pg_escape_string($data_add_item['amount'])."');");
	}

	function update_data($data_update_item) {
		//Plural: Does no updating.
	}

	function delete_data($data_delete_by_member) {
/*
		if (empty($data_delete_by_member)) return;

		foreach ($data_delete_by_member as $data_delete_item) {
			runq("DELETE FROM invoices WHERE member_id='".pg_escape_string($data_delete_item['member_id'])."' AND batch_hash='".pg_escape_string($data_delete_item['batch_hash'])."' AND type='".pg_escape_string($data_delete_item['type'])."' AND status='".pg_escape_string($data_delete_item['status'])."' AND amount='".pg_escape_string($data_delete_item['amount'])."';");
		}
*/
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New PluralTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}
}

?>