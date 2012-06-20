<?php

Class MemberConfluenceStatuses {
	public $ldap;
	public $base;

	function hook_models_required_transforms($data) {
		return array("MemberIds", "MemberNames", "MemberEmails", "MemberWebStatuses");
	}
	function hook_models_required_tables($data) {
		return array();
	}
	function hook_models_required_columns($data) {
		return array();
	}
	function hook_models_transform_priority($data) {
		return "tertiary";
	}
	function hook_extract_index_sql($data) {
		return array();
	}

	function connect_to_ldap() {
		if (!empty($this->ldap)) {
			return;
		}

		$ldaphost = Conf::$member_confluence_statuses_ldaphost;
		$ldapbasedn = Conf::$member_confluence_statuses_ldapbasedn;
		$ldapuser = Conf::$member_confluence_statuses_ldapuser;
		$ldappass = Conf::$member_confluence_statuses_ldappass;

		$this->base = $ldapbasedn;

		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

		$this->ldap = ldap_connect($ldaphost);
		ldap_bind($this->ldap, "cn={$ldapuser},".$this->base, $ldappass);
	}

	function get_src_data($src_member_ids_chunk, $extract_id) {
		return $this->get_src_members_confluence_statuses($src_member_ids_chunk);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_confluence_statuses($src_member_ids_chunk);
	}

	function get_members_confluence_statuses($member_confluence_statuses_query) {
		if (empty($member_confluence_statuses_query)) return null;

		foreach ($member_confluence_statuses_query as $member_confluence_statuses_query_tmp) {
			$member_id = trim($member_confluence_statuses_query_tmp['member_id']);

			$members_confluence_statuses[$member_id]['member_id'] = $member_id;
			$members_confluence_statuses[$member_id]['cn'] = $member_confluence_statuses_query_tmp['cn'];
			$members_confluence_statuses[$member_id]['sn'] = $member_confluence_statuses_query_tmp['sn'];
			$members_confluence_statuses[$member_id]['givenname'] = $member_confluence_statuses_query_tmp['givenname'];
			$members_confluence_statuses[$member_id]['mail'] = $member_confluence_statuses_query_tmp['mail'];
			$members_confluence_statuses[$member_id]['userpassword'] = $member_confluence_statuses_query_tmp['userpassword'];
		}

		return $members_confluence_statuses;
	}

	function get_src_members_confluence_statuses($chunk_id) {
		$src_member_names_query = runq("SELECT DISTINCT * FROM names n INNER JOIN chunk_member_ids ch ON (ch.member_id=n.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		foreach ($src_member_names_query as $src_member_names_tmp) {
			$member_id = $src_member_names_tmp['member_id'];
			$nametype = $src_member_names_tmp['type'];

			$src_member_names[$member_id][$nametype] = $src_member_names_tmp;
		}


		$src_member_emails_query = runq("SELECT DISTINCT * FROM emails e INNER JOIN chunk_member_ids ch ON (ch.member_id=e.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		foreach ($src_member_emails_query as $src_member_emails_tmp) {
			$member_id = $src_member_emails_tmp['member_id'];
			$email_id = $src_member_emails_tmp['id'];

			$src_member_emails[$member_id][$email_id] = $src_member_emails_tmp;
		}


		$src_member_statuses_query = runq("SELECT DISTINCT m.member_id, p.ldap_hash FROM member_ids m INNER JOIN passwords p ON (p.member_id=m.member_id) INNER JOIN web_statuses w ON (w.member_id=m.member_id) INNER JOIN chunk_member_ids ch ON (ch.member_id=m.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		foreach ($src_member_statuses_query as $src_member_statuses_tmp) {
			$member_id = $src_member_statuses_tmp['member_id'];
			$src_member_statuses[$member_id]['member_id'] = $member_id;

			foreach (array("PREF", "OFIC") as $name_type) {
				if (empty($src_member_names[$member_id][$name_type])) continue;

				$src_member_statuses[$member_id]['cn'] = strtolower($src_member_names[$member_id][$name_type]['given_names'].$src_member_names[$member_id][$name_type]['family_name']);
				$src_member_statuses[$member_id]['sn'] = $src_member_names[$member_id][$name_type]['family_name'];
				$src_member_statuses[$member_id]['givenname'] = $src_member_names[$member_id][$name_type]['given_names'];

				break;
			}

			if (!empty($src_member_emails[$member_id])) {
				$newest_email_id = max(array_keys($src_member_emails[$member_id]));
				$src_member_statuses[$member_id]['mail'] = $src_member_emails[$member_id][$newest_email_id]['email'];
			}

			$src_member_statuses[$member_id]['userpassword'] = $src_member_statuses_tmp['ldap_hash'];
		}

		return $this->get_members_confluence_statuses($src_member_statuses);
	}

	function get_dst_members_confluence_statuses($chunk_id) {
		$this->connect_to_ldap();

		$foo = runq("SELECT DISTINCT ch.member_id FROM chunk_member_ids ch WHERE ch.chunk_id='{$chunk_id}';");

		foreach ($foo as $bar) {
			$chunk_member_ids[] = $bar['member_id'];
		}

		$search = ldap_search($this->ldap, $this->base, "(|(uid=".implode(")(uid=", $chunk_member_ids)."))", array("uid", "cn", "sn", "givenname", "mail", "userpassword"));
		$search_results = ldap_get_entries($this->ldap, $search);

		if (empty($search_results)) return null;

		foreach ($search_results as $search_result) {
			if (empty($search_result['uid'][0])) continue;

			$member_id = $search_result['uid'][0];

			$dst_member_statuses[$member_id]['member_id'] = $member_id;
			$dst_member_statuses[$member_id]['cn'] = $search_result['cn'][0];
			$dst_member_statuses[$member_id]['sn'] = $search_result['sn'][0];
			$dst_member_statuses[$member_id]['givenname'] = $search_result['givenname'][0];
			$dst_member_statuses[$member_id]['mail'] = $search_result['mail'][0];
			$dst_member_statuses[$member_id]['userpassword'] = $search_result['userpassword'][0];
		}

		return $this->get_members_confluence_statuses($dst_member_statuses);
	}

	function add_data($data_add_item) {
		$this->connect_to_ldap();

		$add['uid'] = $data_add_item['member_id'];
		$add['objectclass'][0] = "inetOrgPerson";
		
		$add['cn'] = $data_add_item['cn'];
		$add['sn'] = $data_add_item['sn'];
		$add['givenname'] = $data_add_item['givenname'];
		$add['mail'] = $data_add_item['mail'];

		$salt = md5(rand());
		$add['userpassword'] = $data_add_item['userpassword'];
		
		if (empty($add['cn'])) $add['cn'] = " ";
		if (empty($add['sn'])) $add['sn'] = " ";
		if (empty($add['givenname'])) $add['givenname'] = " ";

		ldap_add($this->ldap, "uid={$data_add_item['member_id']},".$this->base, $add);
	}

	function delete_data($data_delete_item) {
		$this->connect_to_ldap();

/* 		runq("DELETE FROM ecpd_statuses WHERE member_id='".pg_escape_string($data_delete_item)."';"); */
	}

	function update_data($data_add_item) {
		$this->connect_to_ldap();

		$add['uid'] = $data_add_item['member_id'];
		$add['objectclass'][0] = "inetOrgPerson";
		
		$add['cn'] = $data_add_item['cn'];
		$add['sn'] = $data_add_item['sn'];
		$add['givenname'] = $data_add_item['givenname'];
		$add['mail'] = $data_add_item['mail'];

		$salt = md5(rand());
		$add['userpassword'] = $data_add_item['userpassword'];
		
		if (empty($add['cn'])) $add['cn'] = " ";
		if (empty($add['sn'])) $add['sn'] = " ";
		if (empty($add['givenname'])) $add['givenname'] = " ";

		ldap_modify($this->ldap, "uid={$data_add_item['member_id']},".$this->base, $add);
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}
}

?>