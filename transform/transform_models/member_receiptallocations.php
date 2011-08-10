<?php

Class MemberReceiptAllocations {
	function hook_models_required_transforms($data) {
		return array("MemberIds", "MemberReceipts", "MemberInvoices", "MemberInvoiceItems");
	}
	function hook_models_required_tables($data) {
		return array("dump_%{extract_id}_receiptallocation" => "ReceiptAllocation");
	}
	function hook_models_transform_priority($data) {
		return "tertiary";
	}
	function hook_extract_index_sql($data) {
		return array(
			"CREATE INDEX dump_%{extract_id}_receiptallocation_invoicebatchid ON dump_%{extract_id}_receiptallocation (cast(invoicebatchid AS BIGINT));",
			"CREATE INDEX dump_%{extract_id}_receiptallocation_invoicebatchposition ON dump_%{extract_id}_receiptallocation (cast(invoicebatchposition AS BIGINT));",
			"CREATE INDEX dump_%{extract_id}_receiptallocation_invoiceitemcode ON dump_%{extract_id}_receiptallocation (trim(invoiceitemcode));",
			"CREATE INDEX dump_%{extract_id}_receiptallocation_invoicesubitemcode ON dump_%{extract_id}_receiptallocation (trim(invoicesubitemcode));",
			"CREATE INDEX dump_%{extract_id}_receiptallocation_receiptbatchid ON dump_%{extract_id}_receiptallocation (cast(receiptbatchid AS BIGINT));",
			"CREATE INDEX dump_%{extract_id}_receiptallocation_receiptbatchposition ON dump_%{extract_id}_receiptallocation (cast(receiptbatchposition AS BIGINT));"
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
			$name['invoiceitem_id'] = trim($member_names_query_tmp['invoiceitem_id']);
			$name['receipt_id'] = trim($member_names_query_tmp['receipt_id']);

			$name_hash = md5(implode("", $name));

			$members_names[$member_id][$name_hash] = $name;
		}

		return $members_names;
	}

	function get_src_members_names($chunk_id, $extract_id) {
		$src_member_names_query = runq("
SELECT DISTINCT 
r.member_id, 
r.id as receipt_id, 
ii.id as invoiceitem_id 
FROM receipts r 
INNER JOIN dump_{$extract_id}_receiptallocation ra ON (ra.receiptbatchid::BIGINT=r.batchid AND ra.receiptbatchposition::BIGINT=r.batchposition)
INNER JOIN invoices i ON (ra.invoicebatchid::BIGINT=i.batchid AND ra.invoicebatchposition::BIGINT=i.batchposition)
INNER JOIN invoiceitems ii ON (ii.invoice_id=i.id AND ii.itemcode=trim(ra.invoiceitemcode) AND ii.subitemcode=trim(ra.invoicesubitemcode))
INNER JOIN chunk_member_ids ch ON (ch.member_id=r.member_id) 
WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_names($src_member_names_query);
	}

	function get_dst_members_names($chunk_id) {
		$dst_member_names_query = runq("SELECT DISTINCT r.member_id, ra.receipt_id, ra.invoiceitem_id FROM receipts r INNER JOIN receiptallocations ra ON (ra.receipt_id=r.id) INNER JOIN chunk_member_ids ch ON (ch.member_id=r.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_names($dst_member_names_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO receiptallocations (receipt_id, invoiceitem_id) VALUES ('".pg_escape_string($data_add_item['receipt_id'])."', '".pg_escape_string($data_add_item['invoiceitem_id'])."');");
	}

	function update_data($data_update_item) {
		//Plural: Does no updating.
	}

	function delete_data($data_delete_by_member) {
/*
		if (empty($data_delete_by_member)) return;

		foreach ($data_delete_by_member as $data_delete_item) {
			runq("DELETE FROM receipts WHERE member_id='".pg_escape_string($data_delete_item['member_id'])."' AND batch_hash='".pg_escape_string($data_delete_item['batch_hash'])."' AND type='".pg_escape_string($data_delete_item['type'])."' AND status='".pg_escape_string($data_delete_item['status'])."' AND type='".pg_escape_string($data_delete_item['type'])."';");
		}
*/
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New PluralTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}
}

?>