#!/usr/bin/php5
<?php

function runq($query) {
	$conn = pg_connect("dbname=hotel user=user");
	$result = pg_query($conn, $query);
	$return = pg_fetch_all($result);
	pg_close($conn);

	return $return;
}

Class Conf {
	public $primary_transforms = array(
		"member_ids"
	);
	public $secondary_transforms = array(
		"passwords",
		"names",
		"emails",
		"addresses",
		"web_statuses",
		"ecpd_statuses"
	);
	public $tertiary_transforms = array(
		"confluence_statuses"
	);

/*
	public $transform_requires = array(
		"member_ids" => "transform_models/member_ids.php",
		"passwords" => "transform_models/member_passwords.php",
		"names" => "transform_models/member_names.php",
		"emails" => "transform_models/member_emails.php",
		"web_statuses" => "transform_models/member_web_statuses.php",
		"ecpd_statuses" => "transform_models/member_ecpd_statuses.php",
		"confluence_statuses" => "transform_models/member_confluence_statuses.php"
	);
*/
}

Class SingleTransforms {
	function transform($src_data_by_members, $dst_data_by_members) {
		if (empty($src_data_by_members)) {
			echo "no source data\n";
			return null;
		}

		$data_add = array();
		$data_nochange = array();
		$data_update = array();
		$data_delete = $dst_data_by_members;
		$data_delete_count = 0;

		foreach ($src_data_by_members as $member_id => $src_data_member) {
			$dst_data_member = $dst_data_by_members[$member_id];

			if (empty($dst_data_member)) {
				$data_add[] = $src_data_member;
			} else if ($dst_data_member != $src_data_member) {
				$data_nochange[] = $src_data_member;
			} else {
				$data_update[] = $src_data_member;
			}

			unset($data_delete[$member_id]);
		}

		$data_delete_count = count($data_delete);

		return array($data_add, $data_nochange, $data_update, $data_delete, $data_delete_count);
	}
}

Class PluralTransforms {
	function transform($src_data_by_members, $dst_data_by_members) {
		if (empty($src_data_by_members)) {
			echo "no source data\n";
			return null;
		}

		$data_add = array();
		$data_nochange = array();
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
					$data_nochange[] = $src_data_member_data;
				}

				unset($data_delete[$member_id][$name_hash]);
			}
		}

		if (!empty($data_delete)) {
			foreach ($data_delete as $data_delete_tmp) {
				$data_delete_count += count($data_delete_tmp);
			}
		}

		return array($data_add, $data_nochange, $data_update, $data_delete, $data_delete_count);
	}
}

class Chunks {
	function create_chunks() {
		echo "creating chunks:";

		$global_timer = microtime(true);

		$chunk_size = 10000;
		$max_records = $chunk_size * 5;

		$chunk_offset = 0;
		$chunking_complete = false;
		
		while (!$chunking_complete && $chunk_offset < $max_records) {
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
		
			echo ".";
		}
		
		echo "\nchunks created. time elapsed ".round(microtime(true) - $global_timer)."s\n\n";

		return $chunk_ids;
	}
}

class GlobalTiming {
	public $chunk_count;
	public $chunk_durations = array();
	public $chunks_completed = 0;

	function start_timing() {
		$this->start_time = microtime(true);
	}

	function chunk_started() {
		$this->chunk_start_time = microtime(true);
	}

	function chunk_completed() {
		$chunk_duration = microtime(true) - $this->chunk_start_time;

		array_push($this->chunk_durations, $chunk_duration);

		unset($this->chunk_start_time);

		$this->chunks_completed += 1;
	}

	function eta_report() {
		$chunk_number = $this->chunks_completed;
		$total_chunks = $this->chunk_count;

		$time_elapsed = round(array_sum($this->chunk_durations));

		$recent_chunk_times = array_slice($this->chunk_durations, -5);
		$recent_chunk_time_avg = array_sum($recent_chunk_times) / count($recent_chunk_times);

		$time_remaining = round($recent_chunk_time_avg * ($total_chunks - $chunk_number));

		echo "Completed chunk: {$chunk_number} of {$total_chunks}\t";
		echo "{$time_elapsed}s elapsed\t";

		if ($chunk_number < $total_chunks) {
			echo "{$time_remaining}s remaining";
		}

		echo "\n\n";
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



$chunks = New Chunks;
$chunk_ids = $chunks->create_chunks();

$global_timing = New GlobalTiming;
$global_timing->chunk_count = count($chunk_ids);
$global_timing->start_timing();

foreach ($chunk_ids as $chunk_count => $chunk_id) {
	$global_timing->chunk_started();

	$transform_timer = microtime(true);

	$transform_class = New MemberIds;

	unset($src_members, $dst_members);

	$src_members = $transform_class->get_src_members($chunk_id);
	$dst_members = $transform_class->get_dst_members($chunk_id);

	unset($members_add, $members_nochange, $members_update, $members_delete, $members_delete_count);
	list($members_add, $members_nochange, $members_update, $members_delete, $members_delete_count) = $transform_class->transform($src_members, $dst_members);

	if (!empty($members_add)) {
		foreach ($members_add as $member_id) {
			$transform_class->add_member($member_id);
		}
	}

	echo "Members ".str_pad(substr("IDs:", 0, 8), 8)."\t".
	count($members_add)." Added;\t".
	count($members_nochange)." Not Changed;\t".
	count($members_update)." Updated;\t".
	$members_delete_count." Deleted\t".
	round(microtime(true) - $transform_timer, 3)."s\n";

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

		unset($data_add, $data_nochange, $data_update, $data_delete, $data_delete_count);
		list($data_add, $data_nochange, $data_update, $data_delete, $data_delete_count) = $transform_class->transform($src_data_by_members, $dst_data_by_members);

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

		echo "Members ".str_pad(substr(ucwords($transform).":", 0, 8), 8)."\t".
		count($data_add)." Added;\t".
		count($data_nochange)." Not Changed;\t".
		count($data_update)." Updated;\t".
		$data_delete_count." Deleted;\t".
		round(microtime(true) - $transform_timer, 3)."s\n";

		$total['add'][$transform] += count($data_add);
		$total['update'][$transform] += count($data_update);
		$total['delete'][$transform] += $data_delete_count;
	}

	$global_timing->chunk_completed();
	echo $global_timing->eta_report();
}

print_r($total);

?>