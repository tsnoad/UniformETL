<?php

Class MemberAddresses {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_%{extract_id}_address" => "Address");
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}
	function hook_extract_index_sql($data) {
		return array(
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_address_customerid ON dump_%{extract_id}_address (cast(customerid AS BIGINT));"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_address MODIFY COLUMN customerid BIGINT; CREATE INDEX dump_%{extract_id}_address_customerid ON dump_%{extract_id}_address (customerid);")
			)
		);
	}

	function get_src_data($src_member_ids_chunk, $extract_id) {
		return $this->get_src_members_addresses($src_member_ids_chunk, $extract_id);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_addresses($src_member_ids_chunk);
	}

	function get_members_addresses($member_addresses_query) {
		if (empty($member_addresses_query)) return null;

		foreach ($member_addresses_query as $member_addresses_query_tmp) {
			$member_id = trim($member_addresses_query_tmp['member_id']);

			$address['member_id'] = $member_id;
			$address['type'] = trim($member_addresses_query_tmp['type']);
			$address['address'] = trim($member_addresses_query_tmp['address']);
			$address['suburb'] = trim($member_addresses_query_tmp['suburb']);
			$address['state'] = trim($member_addresses_query_tmp['state']);
			$address['postcode'] = trim($member_addresses_query_tmp['postcode']);
			$address['country'] = trim($member_addresses_query_tmp['country']);

			$address_hash = md5(implode("", $address));

			$members_addresses[$member_id][$address_hash] = $address;
		}

		return $members_addresses;
	}

	function get_src_members_addresses($chunk_id, $extract_id) {
		$select_address = db_concat(
			"trim(a.line1)",
			"CASE WHEN length(trim(a.line2))>0 THEN ".db_concat("E'\n'", "trim(a.line2)")." ELSE '' END",
			"CASE WHEN length(trim(a.line3))>0 THEN ".db_concat("E'\n'", "trim(a.line3)")." ELSE '' END"
		);

		$src_member_addresses_query = runq("SELECT DISTINCT a.customerid as member_id, a.addrtypeid as type, {$select_address} as address, a.suburb as suburb, a.state as state, a.postcode as postcode, a.countryid as country FROM dump_{$extract_id}_address a INNER JOIN chunk_member_ids ch ON (ch.member_id=".db_cast_bigint("a.customerid").") WHERE ch.chunk_id='{$chunk_id}' AND a.valid='1';");

		return $this->get_members_addresses($src_member_addresses_query);
	}

	function get_dst_members_addresses($chunk_id) {
		$dst_member_addresses_query = runq("SELECT DISTINCT a.member_id, a.type, a.address, a.suburb, a.state, a.postcode, a.country FROM addresses a INNER JOIN chunk_member_ids ch ON (ch.member_id=a.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_addresses($dst_member_addresses_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO addresses (member_id, type, address, suburb, state, postcode, country) VALUES ('".db_escape($data_add_item['member_id'])."', '".db_escape($data_add_item['type'])."', '".db_escape($data_add_item['address'])."', '".db_escape($data_add_item['suburb'])."', '".db_escape($data_add_item['state'])."', '".db_escape($data_add_item['postcode'])."', '".db_escape($data_add_item['country'])."');");
	}

	function update_data($data_update_item) {
		//needs to be coded
	}

	function delete_data($data_delete_by_member) {
		if (empty($data_delete_by_member)) return;

		foreach ($data_delete_by_member as $data_delete_item) {
			runq("DELETE FROM addresses WHERE member_id='".db_escape($data_delete_item['member_id'])."' AND type='".db_escape($data_delete_item['type'])."' AND address='".db_escape($data_delete_item['address'])."' AND suburb='".db_escape($data_delete_item['suburb'])."' AND state='".db_escape($data_delete_item['state'])."' AND postcode='".db_escape($data_delete_item['postcode'])."' AND country='".db_escape($data_delete_item['country'])."';");
		}
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New PluralTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member_plurals($data) {
		list($member_id) = $data;

		$address_query = runq("SELECT type, address, suburb, state, postcode, country FROM addresses a WHERE a.member_id='".db_escape($member_id)."';");

		if (empty($address_query)) return array("addresses" => array());

		foreach ($address_query as $address_query_tmp) {
			//organise names by name type (there's only ever one name per name type)
			$user['addresses'][$address_query_tmp['type']][] = array("address" => $address_query_tmp['address'], "suburb" => $address_query_tmp['suburb'], "state" => $address_query_tmp['state'], "postcode" => $address_query_tmp['postcode'], "country" => $address_query_tmp['country']);
		}

		return $user;
	}
}

?>