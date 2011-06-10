<?php

Class MemberAddresses {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_address");
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}

	function get_src_data($src_member_ids_chunk) {
		return $this->get_src_members_addresses($src_member_ids_chunk);
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

	function get_src_members_addresses($chunk_id) {
		$src_member_addresses_query = runq("SELECT DISTINCT a.customerid as member_id, a.addrtypeid as type, a.line1 as address, a.suburb as suburb, a.state as state, a.postcode as postcode, a.countryid as country FROM dump_address a INNER JOIN chunk_member_ids ch ON (ch.member_id=a.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}' AND a.valid='1';");

		return $this->get_members_addresses($src_member_addresses_query);
	}

	function get_dst_members_addresses($chunk_id) {
		$dst_member_addresses_query = runq("SELECT DISTINCT a.member_id, a.type, a.address, a.suburb, a.state, a.postcode, a.country FROM addresses a INNER JOIN chunk_member_ids ch ON (ch.member_id=a.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_addresses($dst_member_addresses_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO addresses (member_id, type, address, suburb, state, postcode, country) VALUES ('".pg_escape_string($data_add_item['member_id'])."', '".pg_escape_string($data_add_item['type'])."', '".pg_escape_string($data_add_item['address'])."', '".pg_escape_string($data_add_item['suburb'])."', '".pg_escape_string($data_add_item['state'])."', '".pg_escape_string($data_add_item['postcode'])."', '".pg_escape_string($data_add_item['country'])."');");
	}

	function update_data($data_update_item) {
		//needs to be coded
	}

	function delete_data($data_delete_by_member) {
		if (empty($data_delete_by_member)) return;

		foreach ($data_delete_by_member as $data_delete_item) {
			runq("DELETE FROM addresses WHERE member_id='".pg_escape_string($data_delete_item['member_id'])."' AND type='".pg_escape_string($data_delete_item['type'])."' AND address='".pg_escape_string($data_delete_item['address'])."' AND suburb='".pg_escape_string($data_delete_item['suburb'])."' AND state='".pg_escape_string($data_delete_item['state'])."' AND postcode='".pg_escape_string($data_delete_item['postcode'])."' AND country='".pg_escape_string($data_delete_item['country'])."';");
		}
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New PluralTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member_plurals($data) {
		list($member_id) = $data;

		$address_query = runq("SELECT type, address, suburb, state, postcode, country FROM addresses a WHERE a.member_id='".pg_escape_string($member_id)."';");
		$user['addresses'] = $address_query;

		return $user;
	}
}

?>