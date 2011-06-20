#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/autoload.php");

class Recorder {
	public $extract_id;
	public $transform_id;

	function record_start() {
		runq("INSERT INTO transform_processes (transform_id, extract_id, transform_pid) VALUES ('".pg_escape_string($this->transform_id)."', '".pg_escape_string($this->extract_id)."', '".pg_escape_string(getmypid())."');");

/* 		runq("INSERT INTO transform_stats (transform_id) VALUES ('".pg_escape_string($this->transform_id)."');"); */
	}

	function record_finish() {
		runq("UPDATE transform_processes SET finished=TRUE, finish_date=now() WHERE transform_id='".pg_escape_string($this->transform_id)."';");
	}
}

Class Transform {
	public $recorder;
	public $models;
	public $chunks;
	public $global_timing;

	public $extract_id;
	public $transform_id;
	public $chunk_ids;
	public $extract_process;

	public $stats;

	function init_transform() {
		if (empty($this->extract_id)) {
			die("extract id has not been supplied");
		}

		$transform_id_query = runq("SELECT nextval('transform_processes_transform_id_seq');");
		$this->transform_id = $transform_id_query[0]['nextval'];

		$extract_process_query = runq("SELECT * FROM extract_processes e LEFT OUTER JOIN extract_full ef ON (ef.extract_id=e.extract_id) WHERE e.extract_id='".pg_escape_string($this->extract_id)."' LIMIT 1;");
		$this->extract_process = $extract_process_query[0];

		$this->recorder = New Recorder;
		$this->recorder->transform_id = $this->transform_id;
		$this->recorder->extract_id = $this->extract_id;

		$this->models = New Models;
		$this->models->start();

		$this->chunks = New Chunks;
		$this->chunks->transform_id = $this->transform_id;

		$this->global_timing = New GlobalTiming;

		$this->start_transform();

		$this->chunks();

		$this->finish_transform();
	}

	function deleted_members() {
		$deleted_members_query = runq("SELECT m.* FROM member_ids m LEFT OUTER JOIN dump_cpgcustomer c ON (m.member_id=c.customerid::BIGINT) WHERE c.customerid::BIGINT IS NULL;");

		list($deleted_members_query) = Plugins::hook("transform_deleted-members-query", array($deleted_members_query, $this->extract_process));

		if (!empty($deleted_members_query)) {
			foreach ($deleted_members_query as $deleted_member) {
				print_r("Deleted Member (not enabled): ".$deleted_member['member_id']."\n");
			}

			print_r(count($deleted_members_query)." members deleted (not enabled)\n\n");
		} else {
			print_r("No members to delete\n\n");
		}
	}

	function start_transform() {
		$this->recorder->record_start();

		$this->chunk_ids = $this->chunks->create_chunks();

		$this->global_timing->chunk_count = count($this->chunk_ids);

		$this->deleted_members();

		$this->global_timing->start_timing();
	}

	function finish_transform() {
/* 		print_r($total); */
		
		$this->recorder->record_finish();
	}

	function chunks() {
		foreach ($this->chunk_ids as $chunk_count => $chunk_id) {
			$this->chunk($chunk_id);
		}
	}

	function chunk($chunk_id) {
		$this->start_chunk($chunk_id);

		$this->models($chunk_id);

		$this->finish_chunk($chunk_id);
	}

	function start_chunk($chunk_id) {
		$this->global_timing->chunk_started();

		echo str_pad("Transform", 15, " ")."|";
		echo str_pad("Add", 7, " ")."|";
		echo str_pad("No Chan", 7, " ")."|";
		echo str_pad("Update", 7, " ")."|";
		echo str_pad("Delete", 7, " ")."|";
		echo str_pad("", 8, " ");
		echo "\n";
		echo str_pad("", 15, "-")."+";
		echo str_pad("", 7, "-")."+";
		echo str_pad("", 7, "-")."+";
		echo str_pad("", 7, "-")."+";
		echo str_pad("", 7, "-")."+";
		echo str_pad("", 8, "-");
		echo "\n";
	}

	function finish_chunk($chunk_id) {
		$this->global_timing->chunk_completed();
		$this->global_timing->eta_report();
	}

	function models($chunk_id) {
		foreach ($this->models->transforms as $transform) {
			$this->model($chunk_id, $transform);
		}
	}

	function model($chunk_id, $transform) {
		$this->start_model($chunk_id, $transform);

		$transform_class = $this->models->init_class($transform);

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
				$transform_class->update_data($data_update_item);
			}
		}

		if (!empty($data_delete)) {
			foreach ($data_delete as $data_delete_item) {
				if (!empty($data_delete_item)) {
					$transform_class->delete_data($data_delete_item);
				}
			}
		}

		echo str_pad(substr(ucwords($transform), 0, 15), 15, " ")."|";
		echo str_pad(count($data_add), 7, " ", STR_PAD_LEFT)."|";
		echo str_pad(count($data_nochange), 7, " ", STR_PAD_LEFT)."|";
		echo str_pad(count($data_update), 7, " ", STR_PAD_LEFT)."|";
		echo str_pad($data_delete_count, 7, " ", STR_PAD_LEFT)."|";
		echo str_pad($this->global_timing->transform_completed(), 8, " ", STR_PAD_LEFT);
		echo "\n";

		$this->stats[$transform]['add'] += count($data_add);
		$this->stats[$transform]['nochange'] += count($data_nochange);
		$this->stats[$transform]['update'] += count($data_update);
		$this->stats[$transform]['delete'] += $data_delete_count;
		$this->stats[$transform]['total'] += count($data_add) + count($data_nochange) + count($data_update) + $data_delete_count;

		$this->stats['total']['add'] += count($data_add);
		$this->stats['total']['nochange'] += count($data_nochange);
		$this->stats['total']['update'] += count($data_update);
		$this->stats['total']['delete'] += $data_delete_count;
		$this->stats['total']['total'] += count($data_add) + count($data_nochange) + count($data_update) + $data_delete_count;

/* 		runq("UPDATE transform_stats SET stats='".pg_escape_string(json_encode($this->stats))."' WHERE transform_id='".pg_escape_string($this->transform_id)."';"); */

		$this->finish_model($chunk_id, $transform);
	}

	function start_model($chunk_id, $transform) {
		$this->global_timing->transform_started();
	}

	function finish_model($chunk_id, $transform) {
	}
}

$transform = New Transform;
$transform->extract_id = $_SERVER['argv'][1];
$transform->init_transform();

?>
