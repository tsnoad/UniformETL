<?php

class Chunks {
	public $transform_id;

	function create_chunks() {
		//helpful message
		echo "Creating Chunks:"."\n";

		//start a timer
		$timer = microtime(true);

		//how many members are there?
		$members_count_query = runq("select count(DISTINCT customerid::BIGINT) FROM dump_customer WHERE customerid::BIGINT IS NOT NULL;");
		$members_count = $members_count_query[0]['count'];

		if (empty($members_count)) {
			die("HUHS");
		}

		//helpful message
		echo str_pad("", ceil($members_count / Conf::$chunk_size), ".")." ".ceil($members_count / Conf::$chunk_size)." Chunks to create"."\n";

		//for as many chunks as we need
		for ($chunk_offset = 0; $chunk_offset < $members_count; $chunk_offset += Conf::$chunk_size) {
			try {
				//get the next available chunk id
				$chunk_id_query = runq("SELECT nextval('chunks_chunk_id_seq');");
				$chunk_id = $chunk_id_query[0]['nextval'];

				//keep a record of all the chunks we've created
				$chunk_ids[] = $chunk_id;
	
				//create the chunk
				runq("INSERT INTO chunks (chunk_id, transform_id) VALUES ('".pg_escape_string($chunk_id)."', '".pg_escape_string($this->transform_id)."');");
	
				//add a chunk's worth of member ids to the chunk
				runq("INSERT INTO chunk_member_ids SELECT DISTINCT '".pg_escape_string($chunk_id)."'::BIGINT AS chunk_id, customerid::BIGINT AS member_id FROM dump_customer ORDER BY customerid::BIGINT ASC LIMIT '".pg_escape_string(Conf::$chunk_size)."' OFFSET '".pg_escape_string($chunk_offset)."';");

			} catch (Exception $e) {
				die("could not create chunk");
			}

			//print a dot for each chunk we've created
			echo ".";
		}
		
		//helpful message
		echo " ".count($chunk_ids)." Chunks created. time elapsed ".round(microtime(true) - $timer)."s\n\n";

		//return the array with all the id s of the chunks we've just created
		return $chunk_ids;
	}
}

?>