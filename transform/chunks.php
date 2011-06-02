<?php

class Chunks {
	function create_chunks() {
		echo "creating chunks:";

		$global_timer = microtime(true);

		$chunk_size = 10000;

		$chunk_offset = 0;
		$chunking_complete = false;
		
		while (!$chunking_complete) {
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

?>