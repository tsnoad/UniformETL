<?php

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

	function transform_started() {
		$this->transform_start_time = microtime(true);
	}

	function transform_completed() {
		return round(microtime(true) - $this->transform_start_time, 3)."s";
	}
}

?>