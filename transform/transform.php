#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/autoload.php");

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
	public $extract_process;

	function init_transform() {
		if (empty($this->process_id)) {
			die("extract process id has not been supplied");
		}

		$extract_process_query = runq("SELECT * FROM extract_processes WHERE process_id='".pg_escape_string($this->process_id)."' LIMIT 1;");
		$this->extract_process = $extract_process_query[0];

		$this->recorder = New Recorder;
		$this->recorder->process_id = $this->process_id;

		$this->models = New Models;
		$this->models->conf = $this->conf;
		$this->models->start();

		$this->chunks = New Chunks;
		$this->chunks->process_id = $this->process_id;

		$this->global_timing = New GlobalTiming;

		$this->start_transform();

		$this->chunks();

		$this->finish_transform();
	}

	function deleted_members() {
		$deleted_members_query = runq("SELECT m.* FROM member_ids m LEFT OUTER JOIN dump_cpgcustomer c ON (m.member_id=c.customerid::BIGINT) WHERE c.customerid::BIGINT IS NULL;");

		list($deleted_members_query) = Plugins::hook("transform_deleted-members-query", array($deleted_members_query));

		if (!empty($deleted_members_query)) {
			foreach ($deleted_members_query as $deleted_member) {
				print_r("Deleted Member: ".$deleted_member['member_id']."\n");
			}
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
/*
		echo str_pad("", 15, "-")."+";
		echo str_pad("", 7, "-")."+";
		echo str_pad("", 7, "-")."+";
		echo str_pad("", 7, "-")."+";
		echo str_pad("", 7, "-")."+";
		echo str_pad("", 8, "-");
		echo "\n";
*/

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

		echo str_pad(ucwords($transform), 15, " ")."|";
		echo str_pad(count($data_add), 7, " ", STR_PAD_LEFT)."|";
		echo str_pad(count($data_nochange), 7, " ", STR_PAD_LEFT)."|";
		echo str_pad(count($data_update), 7, " ", STR_PAD_LEFT)."|";
		echo str_pad($data_delete_count, 7, " ", STR_PAD_LEFT)."|";
		echo str_pad($this->global_timing->transform_completed(), 8, " ", STR_PAD_LEFT);
		echo "\n";

		$total['add'][$transform] += count($data_add);
		$total['nochange'][$transform] += count($data_nochange);
		$total['update'][$transform] += count($data_update);
		$total['delete'][$transform] += $data_delete_count;

		$this->finish_model($chunk_id, $transform);
	}

	function start_model($chunk_id, $transform) {
		$this->global_timing->transform_started();
	}

	function finish_model($chunk_id, $transform) {
	}

	function hook_transform_deleted_members_query($data) {
		list($deleted_members_query) = $data;

		print_r($this->extract_process);
		var_dump($this->extract_process['extractor']);

		var_dump($this->extract_process['member_ids']);

		if ($this->extract_process['extractor'] == "latest") {

		} else if ($this->extract_process['extractor'] == "full") {
			$previous_transform_query = runq("SELECT max(t.process_id) FROM extract_processes e INNER JOIN transform_processes t ON (t.process_id=e.process_id) WHERE e.extractor='full' AND t.finished=TRUE AND t.failed=FALSE AND t.process_id!='".pg_escape_string($this->process_id)."';");
			$previous_transform = $previous_transform_query[0]['max'];

			if (!empty($previous_transform)) {
				$and_later_than = "AND process_id>'".pg_escape_string($previous_transform)."'";
			} else {
				$and_later_than = "";
			}

			unset($extracted_member_ids);

			foreach (runq("SELECT * FROM extract_latest WHERE process_id<'".pg_escape_string($this->process_id)."' {$and_later_than};") as $previous_extract) {
				$extracted_member_ids_tmp = explode(",", trim($previous_extract['member_ids'], "{}"));

				$extracted_member_ids = array_merge((array)$extracted_member_ids, (array)$extracted_member_ids_tmp);
			}

			if (!empty($deleted_members_query)) {
				foreach ($deleted_members_query as $deleted_member) {
					if (in_array($deleted_member['member_id'], $extracted_member_ids)) {
						print_r("Not deleting Member: ".$deleted_member['member_id']."\n");
					}
				}
			}
		}
	}
}

$transform = New Transform;
$transform->process_id = $_SERVER['argv'][1];
$conf = New Conf;
$transform->conf = $conf;
$transform->init_transform();

?>
