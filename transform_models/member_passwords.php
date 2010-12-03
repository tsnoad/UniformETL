<?php

function gandalf_runq($query) {
	$conn = pg_connect("host=192.168.25.232 dbname=ea_mart_auth user=user password=resources");
	$result = pg_query($conn, $query);
	$return = pg_fetch_all($result);
	pg_close($conn);

	return $return;
}

Class MemberPasswords {
	function get_src_data($src_member_ids_chunk) {
		return $this->get_src_members_passwords($src_member_ids_chunk);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_passwords($src_member_ids_chunk);
	}

	function get_members_passwords($member_passwords_query) {
		if (empty($member_passwords_query)) return null;

		foreach ($member_passwords_query as $member_passwords_query_tmp) {
			$member_id = trim($member_passwords_query_tmp['member_id']);

			$password['member_id'] = $member_id;
			$password['password'] = trim($member_emails_query_tmp['password']);
		}

		return $passwords;
	}

	function get_src_members_passwords($chunk_id) {
		$foo = runq("SELECT DISTINCT ch.member_id FROM chunk_member_ids ch WHERE ch.chunk_id='{$chunk_id}';");

		foreach ($foo as $bar) {
			$chunk_member_ids[] = $bar['member_id'];
		}

		$search_results = gandalf_runq("SELECT DISTINCT a.person_id, a.passphrase FROM authentication a WHERE (a.person_id='".implode("' OR a.person_id='", $chunk_member_ids)."') AND a.end_date='infinity';");

		foreach ($search_results as $search_result) {
			if (empty($search_result['person_id'])) continue; 

			$squiggle[] = array("member_id" => $search_result['person_id'], "password" => $search_result['passphrase']);
		}

		return $this->get_members_passwords($squiggle);
	}

	function get_dst_members_passwords($chunk_id) {
		$dst_member_passwords_query = runq("SELECT DISTINCT p.member_id, p.salt, p.hash FROM passwords p INNER JOIN chunk_member_ids ch ON (ch.member_id=p.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_passwords($dst_member_passwords_query);
	}

	function add_data($data_add_item) {
		$salt = md5(rand());
		$hash = md5($salt.$data_add_item['password']);

print_r($data_add_item);

/* 		runq("INSERT INTO passwords (member_id, salt, hash) VALUES ('".pg_escape_string($data_add_item['member_id'])."', '".pg_escape_string($salt)."', '".pg_escape_string($hash)."');"); */
	}

	function delete_data($data_delete_item) {
/* 		runq("DELETE FROM web_statuses WHERE member_id='".pg_escape_string($data_delete_item)."';"); */
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}
}

?>