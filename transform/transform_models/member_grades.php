<?php

Class MemberGrades {
/*
	public static $grade_names = array(
		"AFIL" => "Affiliate",
		"ASOC" => "Associate",
		"COMP" => "Companion",
		"FELL" => "Fellow",
		"GRAD" => "Graduate",
		"HONF" => "Honorary Fellow",
		"MEMB" => "Member",
		"OFEL" => "Officer Fellow",
		"OGRA" => "Officer Graduate",
		"OMEM" => "Officer Member",
		"OSTU" => "Officer Student",
		"SNRM" => "Senior Member",
		"STUD" => "Student (IEAust)",
		"TFEL" => "Technologist Fellow",
		"TGRA" => "Technologist Graduate",
		"TMEM" => "Technologist Member",
		"TSTU" => "Technologist Student"
	);
*/

	function hook_models_required_transforms($data) {
		return array("MemberIds");
	}
	function hook_models_required_tables($data) {
		return array("dump_%{extract_id}_cpgcustomer" => "cpgCustomer");
	}
	function hook_models_required_columns($data) {
		return array("cpgCustomer" => array("customerid", "cpgid", "custstatusid", "gradeid", "supppnenabled"));
	}
	function hook_models_transform_priority($data) {
		return "secondary";
	}
	function hook_extract_index_sql($data) {
		return array(
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_cpgcustomer_cpgid ON dump_%{extract_id}_cpgcustomer (cpgid) WHERE (cpgid='IEA');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_cpgcustomer MODIFY COLUMN cpgid VARCHAR(32); CREATE INDEX dump_%{extract_id}_cpgcustomer_cpgid ON dump_%{extract_id}_cpgcustomer (cpgid);")
			),
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_cpgcustomer_customerid ON dump_%{extract_id}_cpgcustomer (cast(customerid AS BIGINT)) WHERE (cpgid='IEA');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_cpgcustomer MODIFY COLUMN customerid BIGINT; CREATE INDEX dump_%{extract_id}_cpgcustomer_customerid ON dump_%{extract_id}_cpgcustomer (customerid);")
			),
			db_choose(
				db_pgsql("CREATE INDEX dump_%{extract_id}_cpgcustomer_custstatusid ON dump_%{extract_id}_cpgcustomer (custstatusid) WHERE (custstatusid='MEMB');"), 
				db_mysql("ALTER TABLE dump_%{extract_id}_cpgcustomer MODIFY COLUMN custstatusid VARCHAR(32); CREATE INDEX dump_%{extract_id}_cpgcustomer_custstatusid ON dump_%{extract_id}_cpgcustomer (custstatusid);")
			)
		);
	}

	function get_src_data($src_member_ids_chunk, $extract_id) {
		return $this->get_src_members_grades($src_member_ids_chunk, $extract_id);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_grades($src_member_ids_chunk);
	}

	function get_members_grades($member_passwords_query) {
		if (empty($member_passwords_query)) return null;

		foreach ($member_passwords_query as $member_passwords_query_tmp) {
			$member_id = trim($member_passwords_query_tmp['member_id']);

			$password['member_id'] = $member_id;
			$password['grade'] = trim($member_passwords_query_tmp['grade']);
			$password['chartered'] = trim($member_passwords_query_tmp['chartered']);

			$members_passwords[$member_id] = $password;
		}

		return $members_passwords;
	}

	function get_src_members_grades($chunk_id, $extract_id) {
		$src_member_passwords_query = runq("SELECT DISTINCT g.customerid as member_id, g.gradeid as grade, g.supppnenabled='1' as chartered FROM dump_{$extract_id}_cpgcustomer g INNER JOIN chunk_member_ids ch ON (ch.member_id=".db_cast_bigint("g.customerid").") WHERE ch.chunk_id='{$chunk_id}' AND g.cpgid='IEA';");

		return $this->get_members_grades($src_member_passwords_query);
	}

	function get_dst_members_grades($chunk_id) {
		$dst_member_passwords_query = runq("SELECT DISTINCT g.member_id, g.grade, g.chartered FROM grades g INNER JOIN chunk_member_ids ch ON (ch.member_id=g.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_grades($dst_member_passwords_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO grades (member_id, grade, chartered) VALUES ('".db_escape($data_add_item['member_id'])."', '".db_escape($data_add_item['grade'])."', '".db_boolean($data_add_item['chartered'])."');");
	}

	function delete_data($data_delete_item) {
		runq("DELETE FROM grades WHERE member_id='".db_escape($data_delete_item['member_id'])."' AND grade='".db_escape($data_delete_item['grade'])."' AND chartered='".db_boolean($data_delete_item['chartered'])."';");
	}

	function update_data($data_update_item) {
		runq("UPDATE grades SET grade='".db_escape($data_update_item['grade'])."', chartered='".db_boolean($data_update_item['chartered'])."' WHERE member_id='".db_escape($data_update_item['member_id'])."';");
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function hook_api_get_member($data) {
		$grade_constants = "(VALUES
('AFIL', 'Affiliate', 'AffilIEAust', ''),
('COMP', 'Companion', 'CompIEAust ', ''),
('FELL', 'Fellow', 'FIEAust', 'CPEng'),
('GRAD', 'Graduate', 'GradIEAust ', ''),
('HONF', 'Honorary Fellow', 'HonFIEAust ', 'CPEng'),
('MEMB', 'Member', 'MIEAust', 'CPEng'),
('OFEL', 'Officer Fellow', 'OFIEAust', 'CEngO'),
('OGRA', 'Officer Graduate', 'GradOIEAust', ''),
('OMEM', 'Officer Member', 'OMIEAust', 'CEngO'),
('OSTU', 'Officer Student', 'StudIEAust', ''),
('SNRM', 'Senior Member', 'SMIEAust', 'CPEng'),
('STUD', 'Student (IEAust)', 'StudIEAust', ''),
('TFEL', 'Technologist Fellow', 'TFIEAust', 'CEngT'),
('TGRA', 'Technologist Graduate', 'GradTIEAust', ''),
('TMEM', 'Technologist Member', 'TMIEAust', 'CEngT'),
('TSTU', 'Technologist Student', 'StudIEAust', '')
) AS gn (grade, name, postnominals, chartered_postnominals)";

		if (Conf::$dblang == "mysql") {
			return array("g.grade AS grade, g.chartered, '' AS grade_postnominals", "LEFT JOIN grades g ON (g.member_id=m.member_id)");
		}

		return array("gn.name AS grade, g.chartered, case when g.chartered then gn.postnominals||' '||gn.chartered_postnominals else gn.postnominals end as grade_postnominals", "LEFT JOIN grades g ON (g.member_id=m.member_id) LEFT JOIN {$grade_constants} ON (gn.grade=g.grade)");
	}
}

?>