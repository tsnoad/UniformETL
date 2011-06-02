#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");

require_once("/etc/uniformetl/transform/singletransforms.php");
require_once("/etc/uniformetl/transform/pluraltransforms.php");
require_once("/etc/uniformetl/transform/chunks.php");
require_once("/etc/uniformetl/transform/globaltiming.php");
require_once("/etc/uniformetl/transform/transform_models.php");

class Recorder {
	public $process_id;

	function record_start() {
		runq("INSERT INTO transform_processes (process_id, transform_pid) VALUES ('".pg_escape_string($this->process_id)."', '".pg_escape_string(getmypid())."');");
	}

	function record_finish() {
		runq("UPDATE transform_processes SET finished=TRUE, finish_date=now() WHERE process_id='".pg_escape_string($this->process_id)."';");
	}
}

Class Transform {
	public $conf;
	public $recorder;
	public $models;
	public $chunks;
	public $global_timing;

	public $process_id;
	public $chunk_ids;

	function init_transform() {
		if (empty($this->process_id)) {
			die("extract process id has not been supplied");
		}

		$this->recorder = New Recorder;
		$this->recorder->process_id = $this->process_id;

		$this->models = New Models;
		$this->models->conf = $this->conf;
		$this->models->start();

		$this->chunks = New Chunks;
		$this->chunks->process_id = $this->process_id;

		$this->global_timing = New GlobalTiming;
		$this->global_timing->chunk_count = count($this->chunk_ids);

		$this->start_transform();

		$this->chunks();

		$this->finish_transform();
	}

	function deleted_members() {
		$deleted_members_query = runq("SELECT m.* FROM member_ids m LEFT OUTER JOIN dump_cpgcustomer c ON (m.member_id=c.customerid::BIGINT) WHERE c.customerid::BIGINT IS NULL;");

		if (!empty($deleted_members_query)) {
			foreach ($deleted_members_query as $deleted_member) {
				print_r("Deleted Member: ".$deleted_member['member_id']."\n");
			}
		}
	}

	function start_transform() {
		$this->recorder->record_start();

		$this->chunk_ids = $this->chunks->create_chunks();

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

		$this->finish_model($chunk_id, $transform);
	}

	function start_model($chunk_id, $transform) {
		$transform_timer = microtime(true);
	}

	function finish_model($chunk_id, $transform) {
	}
}

$transform = New Transform;
$transform->process_id = $_SERVER['argv'][1];
$conf = New Conf;
$transform->conf = $conf;
$transform->init_transform();

?>
