<?php

Class MemberSocieties {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_%{extract_id}_cpgcustomer" => "cpgCustomer");
	}
	function hook_models_required_columns($data) {
		return array(
			"cpgCustomer" => array("customerid", "cpgid", "gradeid", "custstatusid")
		);
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}
	function hook_extract_index_sql($data) {
		return array(
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_cpgcustomer_cpgid_notiea ON dump_%{extract_id}_cpgcustomer (cpgid) WHERE (cpgid!='IEA');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_cpgcustomer MODIFY COLUMN cpgid VARCHAR(32); CREATE INDEX dump_%{extract_id}_cpgcustomer_cpgid ON dump_%{extract_id}_cpgcustomer (cpgid);")
			),
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_cpgcustomer_customerid_notiea ON dump_%{extract_id}_cpgcustomer (cast(customerid AS BIGINT)) WHERE (cpgid!='IEA');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_cpgcustomer MODIFY COLUMN customerid BIGINT; CREATE INDEX dump_%{extract_id}_cpgcustomer_customerid ON dump_%{extract_id}_cpgcustomer (customerid);")
			)
		);
	}

	function get_src_data($src_member_ids_chunk, $extract_id) {
		return $this->get_src_members_emails($src_member_ids_chunk, $extract_id);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_emails($src_member_ids_chunk);
	}

	function get_members_emails($member_emails_query) {
		if (empty($member_emails_query)) return null;

		foreach ($member_emails_query as $member_emails_query_tmp) {
			$member_id = trim($member_emails_query_tmp['member_id']);

			$email['member_id'] = $member_id;
			$email['society'] = trim($member_emails_query_tmp['society']);
			$email['grade'] = trim($member_emails_query_tmp['grade']);

			$email_hash = md5(implode("", $email));

			$members_emails[$member_id][$email_hash] = $email;
		}

		return $members_emails;
	}

	function get_src_members_emails($chunk_id, $extract_id) {
		$src_member_emails_query = runq("SELECT DISTINCT c.customerid::BIGINT AS member_id, c.cpgid AS society, c.gradeid AS grade FROM dump_{$extract_id}_cpgcustomer c INNER JOIN chunk_member_ids ch ON (ch.member_id=c.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}' AND trim(c.cpgid)!='IEA' AND trim(c.custstatusid)='MEMB';");

		return $this->get_members_emails($src_member_emails_query);
	}

	function get_dst_members_emails($chunk_id) {
		$dst_member_emails_query = runq("SELECT DISTINCT c.member_id, c.society, c.grade FROM societies c INNER JOIN chunk_member_ids ch ON (ch.member_id=c.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_emails($dst_member_emails_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO societies (member_id, society, grade) VALUES ('".db_escape($data_add_item['member_id'])."', '".db_escape($data_add_item['society'])."', '".db_escape($data_add_item['grade'])."');");
	}

	function update_data($data_update_item) {
		//Plural: Does no updating.
	}

	function delete_data($data_delete_by_member) {
		if (empty($data_delete_by_member)) return;

		foreach ($data_delete_by_member as $data_delete_item) {
			runq("DELETE FROM societies WHERE member_id='".db_escape($data_delete_item['member_id'])."' AND society='".db_escape($data_delete_item['society'])."' AND grade='".db_escape($data_delete_item['grade'])."';");
		}
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New PluralTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member_plurals($data) {
		list($member_id) = $data;

		$societies_query = runq("SELECT sn.name AS society FROM societies s INNER JOIN society_names sn ON (sn.society=s.society) WHERE member_id='".db_escape($member_id)."';");

		if (empty($societies_query)) return array("societies" => array());

		foreach ($societies_query as $societies_query_tmp) {
			//put societies in array
			$user['societies'][] = $societies_query_tmp['society'];
		}

		return $user;
	}
}

?>