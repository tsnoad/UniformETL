<?php

Class MemberConfluenceStatuses {
	function __construct() {
		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
		$this->ldap = ldap_connect("localhost");
		ldap_bind($this->ldap, "cn=admin,dc=home,dc=local", "admin");		
	}

	function get_src_data($src_member_ids_chunk) {
		return $this->get_src_members_confluence_statuses($src_member_ids_chunk);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_confluence_statuses($src_member_ids_chunk);
	}

	function get_members_confluence_statuses($member_confluence_statuses_query) {
		if (empty($member_confluence_statuses_query)) return null;

		foreach ($member_confluence_statuses_query as $member_confluence_statuses_query_tmp) {
			$member_id = trim($member_confluence_statuses_query_tmp['member_id']);

			$members_confluence_statuses[$member_id] = $member_id;
		}

		return $members_confluence_statuses;
	}

	function get_src_members_confluence_statuses($chunk_id) {
		$foo = runq("SELECT DISTINCT c.customerid as member_id FROM dump_cpgcustomer c INNER JOIN chunk_member_ids ch ON (ch.member_id=c.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}' AND c.custstatusid='MEMB';");

		return $this->get_members_confluence_statuses($foo);
	}

	function get_dst_members_confluence_statuses($chunk_id) {
		$foo = runq("SELECT DISTINCT ch.member_id FROM chunk_member_ids ch WHERE ch.chunk_id='{$chunk_id}';");

		foreach ($foo as $bar) {
			$chunk_member_ids[] = $bar['member_id'];
		}

		$search = ldap_search($this->ldap, "dc=home,dc=local", "(|(uid=".implode(")(uid=", $chunk_member_ids)."))", array("uid"));
		$search_results = ldap_get_entries($this->ldap, $search);

		foreach ($search_results as $search_result) {
			if (empty($search_result['uid'][0])) continue; 

			$squiggle[] = array("member_id" => $search_result['uid'][0]);
		}

		return $this->get_members_confluence_statuses($squiggle);
	}

	function add_data($data_add_item) {
		$add['uid'] = $data_add_item;
		$add['objectclass'][0] = "inetOrgPerson";
		
		$add['cn'] = "gary seven";
		$add['sn'] = "seven";
		$add['givenname'] = "gary";
		$add['mail'] = "seven@example.com";
		$add['userpassword'] = "squiggles";
		
		ldap_add($this->ldap, "uid={$data_add_item},ou=people,dc=home,dc=local", $add);
	}

	function delete_data($data_delete_item) {
/* 		runq("DELETE FROM ecpd_statuses WHERE member_id='".pg_escape_string($data_delete_item)."';"); */
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}
}

?>