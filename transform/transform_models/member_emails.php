<?php

Class MemberEmails {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_%{extract_id}_email" => "EMail");
	}
	function hook_models_required_columns($data) {
		return array("EMail" => array("customerid", "emailtypeid", "emailaddress"));
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}
	function hook_extract_index_sql($data) {
		return array(
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_email_emailtypeid ON dump_%{extract_id}_email (emailtypeid) WHERE (emailtypeid='INET');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_email MODIFY COLUMN emailtypeid VARCHAR(32); CREATE INDEX dump_%{extract_id}_email_emailtypeid ON dump_%{extract_id}_email (emailtypeid);")
			),
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_email_customerid ON dump_%{extract_id}_email (cast(customerid AS BIGINT)) WHERE (emailtypeid='INET');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_email MODIFY COLUMN customerid BIGINT; CREATE INDEX dump_%{extract_id}_email_customerid ON dump_%{extract_id}_email (customerid);")
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
			$email['email'] = trim($member_emails_query_tmp['email']);

			$email_hash = md5(implode("", $email));

			$members_emails[$member_id][$email_hash] = $email;
		}

		return $members_emails;
	}

	function get_src_members_emails($chunk_id, $extract_id) {
		$src_member_emails_query = runq("SELECT DISTINCT e.customerid as member_id, e.emailaddress as email FROM dump_{$extract_id}_email e INNER JOIN chunk_member_ids ch ON (ch.member_id=".db_cast_bigint("e.customerid").") WHERE ch.chunk_id='{$chunk_id}' AND e.emailtypeid='INET';");

		return $this->get_members_emails($src_member_emails_query);
	}

	function get_dst_members_emails($chunk_id) {
		$dst_member_emails_query = runq("SELECT DISTINCT e.member_id, e.email FROM emails e INNER JOIN chunk_member_ids ch ON (ch.member_id=e.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_emails($dst_member_emails_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO emails (member_id, email) VALUES ('".db_escape($data_add_item['member_id'])."', '".db_escape($data_add_item['email'])."');");
	}

	function update_data($data_update_item) {
		//Plural: Does no updating.
	}

	function delete_data($data_delete_by_member) {
		if (empty($data_delete_by_member)) return;

		foreach ($data_delete_by_member as $data_delete_item) {
			runq("DELETE FROM emails WHERE member_id='".db_escape($data_delete_item['member_id'])."' AND email='".db_escape($data_delete_item['email'])."';");
		}
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New PluralTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member_plurals($data) {
		list($member_id) = $data;

		$emails_query = runq("SELECT email FROM emails e WHERE e.member_id='".db_escape($member_id)."';");

		if (empty($emails_query)) return array("emails" => array());

		foreach ($emails_query as $emails_query_tmp) {
			//put email addresses in array
			$user['emails'][] = $emails_query_tmp['email'];
		}

		return $user;
	}
}

?>