<?php

Class MemberPasswords {
	function hook_models_required_transforms($data) {
		return array("MemberPasswords" => array("MemberIds"));
	}

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
			$password['password'] = trim($member_passwords_query_tmp['password']);
			$password['salt'] = trim($member_passwords_query_tmp['salt']);
			$password['hash'] = trim($member_passwords_query_tmp['hash']);

			$members_passwords[$member_id] = $password;
		}

		return $members_passwords;
	}

	function gandalf_runq($query) {
		$conn = pg_connect("host=".Conf::$member_passwords_dbhost." dbname=".Conf::$member_passwords_dbname." user=".Conf::$member_passwords_dbuser." password=".Conf::$member_passwords_dbpass."");
		$result = pg_query($conn, $query);
		$return = pg_fetch_all($result);
		pg_close($conn);
	
		return $return;
	}

	function get_src_members_passwords($chunk_id) {
		$foo = runq("SELECT DISTINCT ch.member_id FROM chunk_member_ids ch WHERE ch.chunk_id='{$chunk_id}';");

		foreach ($foo as $bar) {
			$chunk_member_ids[] = $bar['member_id'];
		}

		$search_results = $this->gandalf_runq("SELECT DISTINCT a.person_id, a.passphrase FROM authentication a WHERE (a.person_id='".implode("' OR a.person_id='", $chunk_member_ids)."') AND a.end_date='infinity' AND passphrase IS NOT NULL AND passphrase!='';");

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

	function make_salt() {
		$sea = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$sea_size = strlen($sea);

		$salt_size = 32;

		for ($i = 0; $i < $salt_size; $i ++) {
			$salt .= substr($sea, rand(0, $sea_size - 1), 1);
		}

		return $salt;
	}

	function add_data($data_add_item) {
		$salt = $this->make_salt();
		$hash = md5($salt.$data_add_item['password']);

		$ldap_salt = $this->make_salt();
		$ldap_hash = "{SSHA}".base64_encode(pack("H*",sha1($data_add_item['password'].$ldap_salt)).$ldap_salt);

		runq("INSERT INTO passwords (member_id, salt, hash, ldap_hash) VALUES ('".pg_escape_string($data_add_item['member_id'])."', '".pg_escape_string($salt)."', '".pg_escape_string($hash)."', '".pg_escape_string($ldap_hash)."');");
	}

	function delete_data($data_delete_item) {
/* 		runq("DELETE FROM web_statuses WHERE member_id='".pg_escape_string($data_delete_item)."';"); */
	}

	function update_data($data_add_item) {
		$salt = $this->make_salt();
		$hash = md5($salt.$data_add_item['password']);

		$ldap_salt = $this->make_salt();
		$ldap_hash = "{SSHA}".base64_encode(pack("H*",sha1($data_add_item['password'].$ldap_salt)).$ldap_salt);

		runq("UPDATE passwords SET salt='".pg_escape_string($salt)."', hash='".pg_escape_string($hash)."', ldap_hash='".pg_escape_string($ldap_hash)."' WHERE member_id='".pg_escape_string($data_add_item['member_id'])."';");
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}

	function update_or_add_data($data_item) {
		$member_id = $data_item['member_id'];

		$existing_data_count = runq("SELECT count(*) FROM passwords WHERE member_id='".pg_escape_string($data_item['member_id'])."';");
		$existing_data_count = $existing_data_count[0]['count'];

		if ($existing_data_count > 0) {
			$this->update_data($data_item);
		} else {
			$this->add_data($data_item);
		}
	}
}

?>