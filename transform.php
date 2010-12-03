#!/usr/bin/php5
<?php

function runq($query) {
	$conn = pg_connect("dbname=hotel user=user");
	$result = pg_query($conn, $query);
	$return = pg_fetch_all($result);
	pg_close($conn);

	return $return;
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

require_once("transform_models/member_ids.php");
require_once("transform_models/member_passwords.php");
require_once("transform_models/member_names.php");
require_once("transform_models/member_emails.php");
require_once("transform_models/member_addresses.php");
require_once("transform_models/member_web_statuses.php");
require_once("transform_models/member_ecpd_statuses.php");
require_once("transform_models/member_confluence_statuses.php");

$global_timer = microtime(true);

unset($chunk_ids);

$chunk_size = 1000;
$chunk_offset = 0;
$chunking_complete = false;

while (!$chunking_complete && $chunk_offset < $chunk_size * 5) {
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

	foreach (array("passwords", "names", "emails", "addresses", "web_statuses", "ecpd_statuses", "confluence_statuses") as $transform) {
		$transform_timer = microtime(true);

		switch ($transform) {
			case "passwords":
				$transform_class = New MemberPasswords;
				break;
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
			case "confluence_statuses":
				$transform_class = New MemberConfluenceStatuses;
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