#!/usr/bin/php5
<?php

function runq($query) {
	$conn = pg_connect("dbname=hotel user=user");
	$result = pg_query($conn, $query);
	$return = pg_fetch_all($result);
	pg_close($conn);

	return $return;
}


Class MemberIds {
	function get_members($member_query) {
		if (empty($member_query)) return null;

		foreach ($member_query as $member_query_tmp) {
			$member_id = trim($member_query_tmp['member_id']);
			$members[$member_id] = $member_id;
		}

		return $members;
	}

	function get_src_members($chunk_id) {
		$src_member_query = runq("SELECT DISTINCT c.customerid AS member_id FROM dump_cpgcustomer c INNER JOIN chunk_member_ids ch ON (ch.member_id=c.customerid::BIGINT) WHERE c.cpgid='IEA' AND ch.chunk_id='{$chunk_id}';");

		return $this->get_members($src_member_query);
	}

	function get_dst_members($chunk_id) {
		$dst_member_query = runq("SELECT DISTINCT m.member_id FROM member_ids m INNER JOIN chunk_member_ids ch ON (ch.member_id=m.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members($dst_member_query);
	}

	function add_member($member_id) {
		runq("INSERT INTO member_ids (member_id) VALUES ('".pg_escape_string($member_id)."');");
	}

// 	function delete_member($member_id) {
// 		runq("DELETE FROM member_ids WHERE member_id='".pg_escape_string($member_id)."';");
// 	}

	function transform($src_members, $dst_members) {
		$members_add = array();
		$members_update = array();
		$members_delete = $dst_members;
		$data_delete_count = 0;

		foreach ($src_members as $member_id => $src_member) {
			$dst_member = $dst_members[$member_id];

			if (empty($dst_member)) {
				$members_add[$member_id] = $member_id;
			} else {
				$members_update[$member_id] = $member_id;
			}

			unset($members_delete[$member_id]);
		}

		$data_delete_count = count($data_delete);

		return array($members_add, $members_update, $members_delete, $data_delete_count);
	}
}

Class MemberNames {
	function get_src_data($src_member_ids_chunk) {
		return $this->get_src_members_names($src_member_ids_chunk);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_names($src_member_ids_chunk);
	}

	function get_members_names($member_names_query) {
		if (empty($member_names_query)) return null;

		foreach ($member_names_query as $member_names_query_tmp) {
			$member_id = trim($member_names_query_tmp['member_id']);

			$name['member_id'] = $member_id;
			$name['type'] = trim($member_names_query_tmp['type']);
			$name['given_names'] = trim($member_names_query_tmp['given_names']);
			$name['family_name'] = trim($member_names_query_tmp['family_name']);

			$name_hash = md5(implode("", $name));

			$members_names[$member_id][$name_hash] = $name;
		}

		return $members_names;
	}

	function get_src_members_names($chunk_id) {
		$src_member_names_query = runq("SELECT DISTINCT n.customerid as member_id, n.nametypeid as type, split_part(n.nameline2, ' ', 1) AS given_names, n.nameline1 as family_name FROM dump_name n INNER JOIN chunk_member_ids ch ON (ch.member_id=n.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_names($src_member_names_query);
	}

	function get_dst_members_names($chunk_id) {
		$dst_member_names_query = runq("SELECT DISTINCT n.member_id, n.type, n.given_names, n.family_name FROM names n INNER JOIN chunk_member_ids ch ON (ch.member_id=n.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_names($dst_member_names_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO names (member_id, type, given_names, family_name) VALUES ('".pg_escape_string($data_add_item['member_id'])."', '".pg_escape_string($data_add_item['type'])."', '".pg_escape_string($data_add_item['given_names'])."', '".pg_escape_string($data_add_item['family_name'])."');");
	}

	function delete_data($data_delete_by_member) {
		if (empty($data_delete_by_member)) return;

		foreach ($data_delete_by_member as $data_delete_item) {
			runq("DELETE FROM names WHERE member_id='".pg_escape_string($data_delete_item['member_id'])."' AND type='".pg_escape_string($data_delete_item['type'])."' AND given_names='".pg_escape_string($data_delete_item['given_names'])."' AND family_name='".pg_escape_string($data_delete_item['family_name'])."';");
		}
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New PluralTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}
}

Class MemberEmails {
	function get_src_data($src_member_ids_chunk) {
		return $this->get_src_members_emails($src_member_ids_chunk);
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

	function get_src_members_emails($chunk_id) {
		$src_member_emails_query = runq("SELECT DISTINCT e.customerid as member_id, e.emailaddress as email FROM dump_email e INNER JOIN chunk_member_ids ch ON (ch.member_id=e.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}' AND e.emailtypeid='INET';");

		return $this->get_members_emails($src_member_emails_query);
	}

	function get_dst_members_emails($chunk_id) {
		$dst_member_emails_query = runq("SELECT DISTINCT e.member_id, e.email FROM emails e INNER JOIN chunk_member_ids ch ON (ch.member_id=e.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_emails($dst_member_emails_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO emails (member_id, email) VALUES ('".pg_escape_string($data_add_item['member_id'])."', '".pg_escape_string($data_add_item['email'])."');");
	}

	function delete_data($data_delete_by_member) {
		if (empty($data_delete_by_member)) return;

		foreach ($data_delete_by_member as $data_delete_item) {
			runq("DELETE FROM emails WHERE member_id='".pg_escape_string($data_delete_item['member_id'])."' AND email='".pg_escape_string($data_delete_item['email'])."';");
		}
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New PluralTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}
}

Class MemberAddresses {
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
}

Class MemberWebStatuses {
	function get_src_data($src_member_ids_chunk) {
		return $this->get_src_members_web_statuses($src_member_ids_chunk);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_web_statuses($src_member_ids_chunk);
	}

	function get_members_web_statuses($member_web_statuses_query) {
		if (empty($member_web_statuses_query)) return null;

		foreach ($member_web_statuses_query as $member_web_statuses_query_tmp) {
			$member_id = trim($member_web_statuses_query_tmp['member_id']);

			$members_web_statuses[$member_id] = $member_id;
		}

		return $members_web_statuses;
	}

	function get_src_members_web_statuses($chunk_id) {
		$src_member_ecpd_statuses_query = runq("SELECT DISTINCT c.customerid as member_id FROM dump_cpgcustomer c INNER JOIN chunk_member_ids ch ON (ch.member_id=c.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}' AND c.custstatusid='MEMB';");

		return $this->get_members_web_statuses($src_member_ecpd_statuses_query);
	}

	function get_dst_members_web_statuses($chunk_id) {
		$dst_member_ecpd_statuses_query = runq("SELECT DISTINCT w.member_id FROM web_statuses w INNER JOIN chunk_member_ids ch ON (ch.member_id=w.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_web_statuses($dst_member_ecpd_statuses_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO web_statuses (member_id) VALUES ('".pg_escape_string($data_add_item)."');");
	}

	function delete_data($data_delete_item) {
		runq("DELETE FROM web_statuses WHERE member_id='".pg_escape_string($data_delete_item)."';");
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}
}

Class MemberEcpdStatuses {
	function get_src_data($src_member_ids_chunk) {
		return $this->get_src_members_ecpd_statuses($src_member_ids_chunk);
	}

	function get_dst_data($src_member_ids_chunk) {
		return $this->get_dst_members_ecpd_statuses($src_member_ids_chunk);
	}

	function get_members_ecpd_statuses($member_ecpd_statuses_query) {
		if (empty($member_ecpd_statuses_query)) return null;

		foreach ($member_ecpd_statuses_query as $member_ecpd_statuses_query_tmp) {
			$member_id = trim($member_ecpd_statuses_query_tmp['member_id']);

			$members_ecpd_statuses[$member_id] = $member_id;
		}

		return $members_ecpd_statuses;
	}

	function get_src_members_ecpd_statuses($chunk_id) {
		$src_member_ecpd_statuses_query = runq("SELECT DISTINCT g.customerid as member_id FROM dump_groupmember g INNER JOIN chunk_member_ids ch ON (ch.member_id=g.customerid::BIGINT) WHERE ch.chunk_id='{$chunk_id}' AND g.groupid='6052';");

		return $this->get_members_ecpd_statuses($src_member_ecpd_statuses_query);
	}

	function get_dst_members_ecpd_statuses($chunk_id) {
		$dst_member_ecpd_statuses_query = runq("SELECT DISTINCT e.member_id FROM ecpd_statuses e INNER JOIN chunk_member_ids ch ON (ch.member_id=e.member_id) WHERE ch.chunk_id='{$chunk_id}';");

		return $this->get_members_ecpd_statuses($dst_member_ecpd_statuses_query);
	}

	function add_data($data_add_item) {
		runq("INSERT INTO ecpd_statuses (member_id) VALUES ('".pg_escape_string($data_add_item)."');");
	}

	function delete_data($data_delete_item) {
		runq("DELETE FROM ecpd_statuses WHERE member_id='".pg_escape_string($data_delete_item)."';");
	}

	function transform($src_data_by_members, $dst_data_by_members) {
		$transform = New SingleTransforms;

		return $transform->transform($src_data_by_members, $dst_data_by_members);
	}
}

Class SingleTransforms {
	function transform($src_data_by_members, $dst_data_by_members) {
		if (empty($src_data_by_members)) {
			echo "no source data\n";
			return null;
		}

		$data_add = array();
		$data_update = array();
		$data_delete = $dst_data_by_members;
		$data_delete_count = 0;

		foreach ($src_data_by_members as $member_id => $src_data_member) {
			$dst_data_member = $dst_data_by_members[$member_id];

			if (empty($dst_data_member)) {
				$data_add[] = $src_data_member;
			} else {
				$data_update[] = $src_data_member;
			}

			unset($data_delete[$member_id]);
		}

		$data_delete_count = count($data_delete);

		return array($data_add, $data_update, $data_delete, $data_delete_count);
	}
}

Class PluralTransforms {
	function transform($src_data_by_members, $dst_data_by_members) {
		if (empty($src_data_by_members)) {
			echo "no source data\n";
			return null;
		}

		$data_add = array();
		$data_update = array();
		$data_delete = $dst_data_by_members;
		$data_delete_count = 0;

		foreach ($src_data_by_members as $member_id => $src_data_member) {
			$dst_data_member = $dst_data_by_members[$member_id];

			$dst_data_member_hashes = array();

			if (!empty($dst_data_member)) {
				$dst_data_member_hashes = array_keys($dst_data_member);
			}

			foreach ($src_data_member as $name_hash => $src_data_member_data) {
				if (!in_array($name_hash, $dst_data_member_hashes)) {
					$data_add[] = $src_data_member_data;
				} else {
					$data_update[] = $src_data_member_data;
				}

				unset($data_delete[$member_id][$name_hash]);
			}
		}

		if (!empty($data_delete)) {
			foreach ($data_delete as $data_delete_tmp) {
				$data_delete_count += count($data_delete_tmp);
			}
		}

		return array($data_add, $data_update, $data_delete, $data_delete_count);
	}
}

$global_timer = microtime(true);

unset($chunk_ids);

$chunk_size = 10000;
$chunk_offset = 0;
$chunking_complete = false;

while (!$chunking_complete && $chunk_offset < $chunk_size * 32) {
	$chunk_id_query = runq("SELECT nextval('chunks_chunk_id_seq');");
	$chunk_id = $chunk_id_query[0]['nextval'];

	$chunk_ids[] = $chunk_id;

	runq("INSERT INTO chunks VALUES ('{$chunk_id}');");

	runq("INSERT INTO chunk_member_ids SELECT DISTINCT '{$chunk_id}'::BIGINT AS chunk_id, customerid::BIGINT AS member_id FROM dump_cpgcustomer WHERE cpgid='IEA' ORDER BY customerid::BIGINT ASC LIMIT {$chunk_size} OFFSET {$chunk_offset};");

	$chunk_members_query = runq("SELECT count(*) FROM chunk_member_ids WHERE chunk_id='{$chunk_id}';");

	if ($chunk_members_query[0]['count'] < $chunk_size) {
		$chunking_complete = true;
	}

	$chunk_offset += $chunk_size;
}

echo "chunks created. time elapsed ".round(microtime(true) - $global_timer)."s\n";
$global_timer = microtime(true);

$chunk_times = array(0);

foreach ($chunk_ids as $chunk_count => $chunk_id) {
	$chunk_number = $chunk_count + 1;
	$time_elapsed = microtime(true) - $global_timer;
	$time_remaining = array_sum($chunk_times) / count($chunk_times) * (count($chunk_ids) - $chunk_number);
	echo "\n";
	echo "Chunk: ".$chunk_number." of ".count($chunk_ids)."\t".round($time_elapsed)."s elapsed\t".round($time_remaining)."s remaining\n";

	$chunk_timer = microtime(true);

	$transform_class = New MemberIds;

	unset($src_members, $dst_members);

	$src_members = $transform_class->get_src_members($chunk_id);
	$dst_members = $transform_class->get_dst_members($chunk_id);

	unset($members_add, $members_update, $members_delete, $members_delete_count);
	list($members_add, $members_update, $members_delete, $members_delete_count) = $transform_class->transform($src_members, $dst_members);

	if (!empty($members_add)) {
		foreach ($members_add as $member_id) {
			$transform_class->add_member($member_id);
		}
	}

	echo "Members ".str_pad(substr("IDs:", 0, 8), 8)."\t".count($members_add)." Added;\t".count($members_update)." Updated;\t".$members_delete_count." Deleted\t".round(microtime(true) - $chunk_timer, 3)."s\n";

	foreach (array("names", "emails", "addresses", "web_statuses", "ecpd_statuses") as $transform) {
		$transform_timer = microtime(true);

		switch ($transform) {
			case "names":
				$transform_class = New MemberNames;
				break;
			case "emails":
				$transform_class = New MemberEmails;
				break;
			case "addresses":
				$transform_class = New MemberAddresses;
				break;
			case "web_statuses":
				$transform_class = New MemberWebStatuses;
				break;
			case "ecpd_statuses":
				$transform_class = New MemberEcpdStatuses;
				break;
		}

		$src_data_by_members = $transform_class->get_src_data($chunk_id);
		$dst_data_by_members = $transform_class->get_dst_data($chunk_id);

		unset($data_add, $data_update, $data_delete, $data_delete_count);
		list($data_add, $data_update, $data_delete, $data_delete_count) = $transform_class->transform($src_data_by_members, $dst_data_by_members);

		if (!empty($data_add)) {
			foreach ($data_add as $data_add_item) {
				$transform_class->add_data($data_add_item);
			}
		}

		if (!empty($data_delete)) {
			foreach ($data_delete as $data_delete_item) {
				$transform_class->delete_data($data_delete_item);
			}
		}

		echo "Members ".str_pad(substr(ucwords($transform).":", 0, 8), 8)."\t".count($data_add)." Added;\t".count($data_update)." Updated;\t".$data_delete_count." Deleted\t".round(microtime(true) - $transform_timer, 3)."s\n";

		$total['add'][$transform] += count($data_add);
		$total['update'][$transform] += count($data_update);
		$total['delete'][$transform] += $data_delete_count;
	}

	array_unshift($chunk_times, microtime(true) - $chunk_timer);
	$chunk_times = array_slice($chunk_times, 0, 5);
}

print_r($total);

?>