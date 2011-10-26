#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/autoload.php");

class Recorder {
	public $extract_id;
	public $transform_id;

	function record_start() {
		try {
			runq("INSERT INTO transform_processes (transform_id, extract_id, transform_pid) VALUES ('".db_escape($this->transform_id)."', '".db_escape($this->extract_id)."', '".db_escape(getmypid())."');");
		} catch (Exception $e) {
			die("could not create process in database");
		}
	}

	function record_finish() {
		try {
			runq("UPDATE transform_processes SET finished=TRUE, finish_date=now() WHERE transform_id='".db_escape($this->transform_id)."';");
		} catch (Exception $e) {
			die("could not update process in database");
		}
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

		try {
			$this->transform_id = db_nextval("transform_processes", "transform_id");
		} catch (Exception $e) {
			die("could not create process in database");
		}

		$extract_process_query = runq("SELECT * FROM extract_processes e LEFT OUTER JOIN extract_full ef ON (ef.extract_id=e.extract_id) WHERE e.extract_id='".db_escape($this->extract_id)."' LIMIT 1;");
		$this->extract_process = $extract_process_query[0];

		$this->recorder = New Recorder;
		$this->recorder->transform_id = $this->transform_id;
		$this->recorder->extract_id = $this->extract_id;

		$this->models = New Models;
		$this->models->start();

		$this->chunks = New Chunks;
		$this->chunks->transform_id = $this->transform_id;
		$this->chunks->extract_id = $this->extract_id;

		$this->global_timing = New GlobalTiming;

		$this->recorder->record_start();

		$this->deleted_members();

		$chunk_ids = $this->chunks->create_chunks();
		$this->global_timing->chunk_count = count($chunk_ids);

		$this->global_timing->start_timing();

		foreach ($chunk_ids as $chunk_id) {
			$this->global_timing->chunk_started();
			$this->start_chunk($chunk_id);
	
			foreach ($this->models->transforms as $transform) {
				$this->model($chunk_id, $transform);
			}
	
			$this->global_timing->chunk_completed();
			$this->global_timing->eta_report();
		}

		$this->recorder->record_finish();
	}

	function deleted_members() {
		$deleted_members_query = runq("SELECT m.* FROM member_ids m LEFT OUTER JOIN dump_{$this->extract_id}_customer c ON (m.member_id=".db_cast_bigint("c.customerid")." AND c.custtypeid='INDI') WHERE ".db_cast_bigint("c.customerid")." IS NULL LIMIT 10000;");

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

	function start_chunk($chunk_id) {
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

	function model($chunk_id, $transform) {
		$this->global_timing->transform_started();

		$transform_class = New $transform;
		list($src_data_by_members, $dst_data_by_members) = $this->get_src_dst_data($chunk_id, $transform_class);
		list($data_add, $data_nochange, $data_update, $data_delete, $data_delete_count) = $transform_class->transform($src_data_by_members, $dst_data_by_members);

		$this->add_data($transform_class, $data_add);
		$this->data_nochange($transform_class, $data_nochange);
		$this->data_update($transform_class, $data_update);
		$this->data_delete($transform_class, $data_delete);

		$this->model_stats($transform, $data_add, $data_nochange, $data_update, $data_delete_count);
	}

	function get_src_dst_data($chunk_id, $transform_class) {
		try {
			$src_data_by_members = $transform_class->get_src_data($chunk_id, $this->extract_id);
			$dst_data_by_members = $transform_class->get_dst_data($chunk_id);
		} catch (Exception $e) {
			die($e->getMessage());
		}

		return array($src_data_by_members, $dst_data_by_members);
	}

	function add_data($transform_class, $data_add) {
		if (empty($data_add)) {
			return;
		}

		foreach ($data_add as $data_add_item) {
			try {
				$transform_class->add_data($data_add_item);
			} catch (Exception $e) {
				print_r($e->getMessage());
				continue;
			}
			echo "+";
		}
		echo "\n";
	}

	function data_nochange($transform_class, $data_nochange) {
		if (empty($data_nochange)) {
			return;
		}
	}

	function data_update($transform_class, $data_update) {
		if (empty($data_update)) {
			return;
		}

		foreach ($data_update as $data_update_item) {
			try {
				$transform_class->update_data($data_update_item);
			} catch (Exception $e) {
				print_r($e->getMessage());
				continue;
			}
			echo "/";

			Plugins::hook("transform_update", array($transform, $data_update_item));
		}
		echo "\n";
	}

	function data_delete($transform_class, $data_delete) {
		if (empty($data_delete)) {
			return;
		}

		foreach ($data_delete as $data_delete_item) {
			if (empty($data_delete_item)) {
				continue;
			}

			try {
				$transform_class->delete_data($data_delete_item);
			} catch (Exception $e) {
				print_r($e->getMessage);
				continue;
			}
			echo "-";
		}
		echo "\n";
	}

	function model_stats($transform, $data_add, $data_nochange, $data_update, $data_delete_count) {
		echo str_pad(substr(ucwords($transform), 0, 15), 15, " ")."|";
		echo str_pad(count($data_add), 7, " ", STR_PAD_LEFT)."|";
		echo str_pad(count($data_nochange), 7, " ", STR_PAD_LEFT)."|";
		echo str_pad(count($data_update), 7, " ", STR_PAD_LEFT)."|";
		echo str_pad($data_delete_count, 7, " ", STR_PAD_LEFT)."|";
		echo str_pad($this->global_timing->transform_completed(), 8, " ", STR_PAD_LEFT);
		echo "\n";

		if (!isset($this->stats[$transform])) {
			$this->stats[$transform] = array("add" => 0, "nochange" => 0, "update" => 0, "delete" => 0, "total" => 0);
		}

		$this->stats[$transform]['add'] += count($data_add);
		$this->stats[$transform]['nochange'] += count($data_nochange);
		$this->stats[$transform]['update'] += count($data_update);
		$this->stats[$transform]['delete'] += $data_delete_count;
		$this->stats[$transform]['total'] += count($data_add) + count($data_nochange) + count($data_update) + $data_delete_count;

		if (!isset($this->stats['total'])) {
			$this->stats['total'] = array("add" => 0, "nochange" => 0, "update" => 0, "delete" => 0, "total" => 0);
		}

		$this->stats['total']['add'] += count($data_add);
		$this->stats['total']['nochange'] += count($data_nochange);
		$this->stats['total']['update'] += count($data_update);
		$this->stats['total']['delete'] += $data_delete_count;
		$this->stats['total']['total'] += count($data_add) + count($data_nochange) + count($data_update) + $data_delete_count;
	}
}

$transform = New Transform;
$transform->extract_id = $_SERVER['argv'][1];
$transform->init_transform();

?>
