<?php

Class MemberColleges {
	public static $college_names = array(
		"BIOM" => "Biomedical College",
		"CHEM" => "Chemical College",
		"CIVL" => "Civil College",
		"ELEC" => "Electrical College",
		"ENVI" => "Environmental College",
		"ITEL" => "Info Telecom & Electronics Eng College",
		"MECH" => "Mechanical College",
		"STRU" => "Structural College"
	);

	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array(
			"dump_%{extract_id}_gradehistory" => "GradeHistory",
			"dump_%{extract_id}_cpggradetype" => "cpgGradeType"
		);
	}
	function hook_models_required_columns($data) {
		return array(
			"GradeHistory" => array("customerid", "cpgid", "gradetypeid", "gradeid", "datechange", "changereasonid"),
			"cpgGradeType" => array("gradetypeid", "classid")
		);
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}
	function hook_extract_index_sql($data) {
		return array(
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_gradehistory_customerid ON dump_%{extract_id}_gradehistory (cast(customerid AS BIGINT));"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_gradehistory MODIFY COLUMN customerid BIGINT; CREATE INDEX dump_%{extract_id}_gradehistory_customerid ON dump_%{extract_id}_gradehistory (customerid);")
			),
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_gradehistory_cpgid ON dump_%{extract_id}_gradehistory (trim(cpgid));"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_gradehistory MODIFY COLUMN cpgid VARCHAR(32); UPDATE dump_%{extract_id}_gradehistory SET cpgid=trim(cpgid); CREATE INDEX dump_%{extract_id}_gradehistory_cpgid ON dump_%{extract_id}_gradehistory (cpgid);")
			),
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_gradehistory_gradetypeid ON dump_%{extract_id}_gradehistory (gradetypeid);"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_gradehistory MODIFY COLUMN gradetypeid VARCHAR(32); CREATE INDEX dump_%{extract_id}_gradehistory_gradetypeid ON dump_%{extract_id}_gradehistory (gradetypeid);")
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
			$email['college'] = trim($member_emails_query_tmp['college']);
			$email['grade'] = trim($member_emails_query_tmp['grade']);

			$email_hash = md5(implode("", $email));

			$members_emails[$member_id][$email_hash] = $email;
		}

		return $members_emails;
	}

	function get_src_members_emails($chunk_id, $extract_id) {
		$src_member_emails_query = runq("SELECT DISTINCT c.customerid::BIGINT as member_id, c.gradetypeid as college, c.gradeid as grade FROM dump_{$extract_id}_gradehistory c INNER JOIN dump_{$extract_id}_cpggradetype gt ON (gt.gradetypeid=c.gradetypeid) INNER JOIN chunk_member_ids ch ON (ch.member_id=c.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}' AND trim(c.cpgid)='IEA' AND trim(gt.classid)='COLL' AND c.datechange='' AND c.changereasonid='';");

		return $this->get_members_emails($src_member_emails_query);
	}

	function get_dst_members_emails($chunk_id) {
		$dst_member_emails_query = runq("SELECT DISTINCT c.member_id, c.college, c.grade FROM colleges c INNER JOIN chunk_member_ids ch ON (ch.member_id=c.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_emails($dst_member_emails_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO colleges (member_id, college, grade) VALUES ('".pg_escape_string($data_add_item['member_id'])."', '".pg_escape_string($data_add_item['college'])."', '".pg_escape_string($data_add_item['grade'])."');");
	}

	function update_data($data_update_item) {
		//Plural: Does no updating.
	}

	function delete_data($data_delete_by_member) {
		if (empty($data_delete_by_member)) return;

		foreach ($data_delete_by_member as $data_delete_item) {
			runq("DELETE FROM colleges WHERE member_id='".pg_escape_string($data_delete_item['member_id'])."' AND college='".pg_escape_string($data_delete_item['college'])."' AND grade='".pg_escape_string($data_delete_item['grade'])."';");
		}
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New PluralTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member_plurals($data) {
		list($member_id) = $data;

		$colleges_query = runq("SELECT college FROM colleges WHERE member_id='".pg_escape_string($member_id)."';");

		if (empty($colleges_query)) return array("colleges" => array());

		foreach ($colleges_query as $colleges_query_tmp) {
			//put email addresses in array
			$user['colleges'][] = MemberColleges::$college_names[$colleges_query_tmp['college']];
		}

		return $user;
	}
}

?>