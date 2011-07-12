<?php

Class MemberSocieties {
	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_%{extract_id}_gradehistory" => "GradeHistory");
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}
	function hook_extract_index_sql($data) {
		return array(
			"CREATE INDEX dump_%{extract_id}_gradehistory_customerid ON dump_%{extract_id}_gradehistory (cast(customerid AS BIGINT));",
			"CREATE INDEX dump_%{extract_id}_gradehistory_cpgid ON dump_%{extract_id}_gradehistory (trim(cpgid));",
			"CREATE INDEX dump_%{extract_id}_gradehistory_gradetypeid ON dump_%{extract_id}_gradehistory (gradetypeid);",
			"CREATE INDEX dump_%{extract_id}_gradehistory_dateadmitted ON dump_%{extract_id}_gradehistory (cast(to_timestamp(dateadmitted, 'Mon DD YYYY HH:MI:SS:MSPM') as timestamp));",
/* 			"CREATE VIEW dump_%{extract_id}_gradehistory_latestdateadmitted AS SELECT customerid, gradetypeid, max(cast(to_timestamp(dateadmitted, 'Mon DD YYYY HH:MI:SS:MSPM') as timestamp)) as latestdateadmitted FROM dump_%{extract_id}_gradehistory WHERE trim(cpgid)='IEA' GROUP BY customerid, gradetypeid;" */
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
/* 		$src_member_emails_query = runq("
SELECT DISTINCT c.customerid::BIGINT as member_id, c.gradetypeid as college, c.gradeid as grade 
FROM dump_{$extract_id}_gradehistory c 
INNER JOIN dump_{$extract_id}_gradehistory_latestdateadmitted ca ON (ca.customerid::BIGINT=c.customerid::BIGINT AND ca.gradetypeid=c.gradetypeid AND ca.latestdateadmitted=cast(to_timestamp(c.dateadmitted, 'Mon DD YYYY HH:MI:SS:MSPM') as timestamp)) 
INNER JOIN chunk_member_ids ch ON (ch.member_id=c.customerid::BIGINT) 
WHERE ch.chunk_id='{$chunk_id}' 
AND trim(c.cpgid)='IEA' 
AND trim(c.changereasonid)='' 
AND trim(c.datechange)='';"); */

		$src_member_emails_query = runq("SELECT DISTINCT c.customerid::BIGINT as member_id, c.cpgid as society, '' as grade FROM dump_{$extract_id}_gradehistory c INNER JOIN chunk_member_ids ch ON (ch.member_id=c.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}' AND trim(c.cpgid)!='IEA' AND trim(c.cpgid)!='iea';");


		return $this->get_members_emails($src_member_emails_query);
	}

	function get_dst_members_emails($chunk_id) {
		$dst_member_emails_query = runq("SELECT DISTINCT c.member_id, c.society, c.grade FROM societies c INNER JOIN chunk_member_ids ch ON (ch.member_id=c.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_emails($dst_member_emails_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO societies (member_id, society, grade) VALUES ('".pg_escape_string($data_add_item['member_id'])."', '".pg_escape_string($data_add_item['society'])."', '".pg_escape_string($data_add_item['grade'])."');");
	}

	function update_data($data_update_item) {
		//needs to be coded
	}

	function delete_data($data_delete_by_member) {
		if (empty($data_delete_by_member)) return;

		foreach ($data_delete_by_member as $data_delete_item) {
			runq("DELETE FROM societies WHERE member_id='".pg_escape_string($data_delete_item['member_id'])."' AND society='".pg_escape_string($data_delete_item['society'])."' AND grade='".pg_escape_string($data_delete_item['grade'])."';");
		}
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New PluralTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member_plurals($data) {
		list($member_id) = $data;

		$societies_query = runq("SELECT society FROM societies WHERE member_id='".pg_escape_string($member_id)."';");

		if (empty($societies_query)) return array("societies" => array());

		foreach ($societies_query as $societies_query_tmp) {
			//put email addresses in array
			$user['societies'][] = $societies_query_tmp['society'];
		}

		return $user;
	}
}

?>