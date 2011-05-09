#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");

require_once("/etc/uniformetl/transform/transform_models.php");

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

			if (!empty($src_data_member) && is_array($src_data_member) && array_keys($src_data_member) == array("member_id", "password", "salt", "hash")) {
				if (empty($dst_data_member)) {
					$data_add[] = $src_data_member;
				} else if ($dst_data_member['hash'] != md5($dst_data_member['salt'].$src_data_member['password'])) {
					$data_update[] = $src_data_member;
				} else {
					$data_nochange[] = $src_data_member;
				}
			} else {
				if (empty($dst_data_member)) {
					$data_add[] = $src_data_member;
				} else if ($dst_data_member != $src_data_member) {
					$data_update[] = $src_data_member;
				} else {
					$data_nochange[] = $src_data_member;
				}
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
		$max_records = $chunk_size * 50;

		$chunk_offset = 0;
		$chunking_complete = false;
		
		while (!$chunking_complete && $chunk_offset < $max_records) {
			$chunk_id_query = runq("SELECT nextval('chunks_chunk_id_seq');");
			$chunk_id = $chunk_id_query[0]['nextval'];
		
			$chunk_ids[] = $chunk_id;
		
			runq("INSERT INTO chunks (chunk_id, process_id) VALUES ('".pg_escape_string($chunk_id)."', '".pg_escape_string($this->process_id)."');");
		
			runq("INSERT INTO chunk_member_ids SELECT DISTINCT '".pg_escape_string($chunk_id)."'::BIGINT AS chunk_id, customerid::BIGINT AS member_id FROM dump_cpgcustomer WHERE cpgid='IEA' ORDER BY customerid::BIGINT ASC LIMIT '".pg_escape_string($chunk_size)."' OFFSET '".pg_escape_string($chunk_offset)."';");
		
			$chunk_members_query = runq("SELECT count(*) FROM chunk_member_ids WHERE chunk_id='".pg_escape_string($chunk_id)."';");
		
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

class Processor {
	public $process_id;

	function start_process() {
		runq("INSERT INTO transform_processes (process_id, transform_pid) VALUES ('".pg_escape_string($this->process_id)."', '".pg_escape_string(getmypid())."');");
	}

	function finish_process() {
		runq("UPDATE transform_processes SET finished=TRUE, finish_date=now() WHERE process_id='".pg_escape_string($this->process_id)."';");
	}

	function deleted_members() {
		$deleted_members_query = runq("SELECT m.* FROM member_ids m LEFT OUTER JOIN dump_cpgcustomer c ON (m.member_id=c.customerid::BIGINT) WHERE c.customerid::BIGINT IS NULL;");

		if (!empty($deleted_members_query)) {
			foreach ($deleted_members_query as $deleted_member) {
				print_r("Deleted Member: ".$deleted_member['member_id']."\n");
			}
		}
	}
}



if (empty($_SERVER['argv'][1])) {
	die("extract process id has not been supplied");
}

$conf = New Conf;

$processor = New Processor;
$processor->process_id = $_SERVER['argv'][1];
$processor->start_process();
$processor->deleted_members();

$models = New Models;
$models->conf = $conf;
$models->start();

$chunks = New Chunks;
$chunks->process_id = $processor->process_id;
$chunk_ids = $chunks->create_chunks();

$global_timing = New GlobalTiming;
$global_timing->chunk_count = count($chunk_ids);
$global_timing->start_timing();

foreach ($chunk_ids as $chunk_count => $chunk_id) {
	$global_timing->chunk_started();

	foreach ($models->transforms as $transform) {
		$transform_timer = microtime(true);

		$transform_class = $models->init_class($transform);

		unset($src_data_by_members, $dst_data_by_members);
		$src_data_by_members = $transform_class->get_src_data($chunk_id);
		$dst_data_by_members = $transform_class->get_dst_data($chunk_id);

		unset($data_add, $data_nochange, $data_update, $data_delete, $data_delete_count);
		list($data_add, $data_nochange, $data_update, $data_delete, $data_delete_count) = $transform_class->transform($src_data_by_members, $dst_data_by_members);

		if (!empty($data_add)) {
			foreach ($data_add as $data_add_item) {
				$transform_class->add_data($data_add_item);
			}
		}

		if (!empty($data_nochange)) {
		}

		if (!empty($data_update)) {
			foreach ($data_update as $data_update_item) {
				if ($transform == "confluence_statuses") $transform_class->update_data($data_update_item);
				if ($transform == "passwords") $transform_class->update_data($data_update_item);
			}
		}

		if (!empty($data_delete)) {
			foreach ($data_delete as $data_delete_item) {
				if (!empty($data_delete_item)) {
					$transform_class->delete_data($data_delete_item);
				}
			}
		}

		echo str_pad(substr(ucwords($transform).":", 0, 12), 12)."\t".
		str_pad(count($data_add)." Added;", 8 + strlen(" Added;"), " ", STR_PAD_LEFT)."\t".
		str_pad(count($data_nochange)." Not Changed;", 8 + strlen(" Not Changed;"), " ", STR_PAD_LEFT)."\t".
		str_pad(count($data_update)." Updated;", 8 + strlen(" Updated;"), " ", STR_PAD_LEFT)."\t".
		str_pad($data_delete_count." Deleted;", 8 + strlen(" Deleted;"), " ", STR_PAD_LEFT)."\t".
		round(microtime(true) - $transform_timer, 3)."s\n";

		$total['add'][$transform] += count($data_add);
		$total['nochange'][$transform] += count($data_nochange);
		$total['update'][$transform] += count($data_update);
		$total['delete'][$transform] += $data_delete_count;
	}

	$global_timing->chunk_completed();
	$global_timing->eta_report();
}

print_r($total);

$processor->finish_process();

?>