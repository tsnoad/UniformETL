<?php

require_once("/etc/uniformetl/autoload.php");

class Chunks {
	public $transform_id;
	public $extract_id;

	function create_chunks() {
		//helpful message
		echo "Creating Chunks:"."\n";

		//start a timer
		$timer = microtime(true);

		//how many members are there?
		$members_count_query = runq("select count(DISTINCT ".db_cast_bigint("customerid").") AS count FROM dump_{$this->extract_id}_customer WHERE ".db_cast_bigint("customerid")." IS NOT NULL AND custtypeid='INDI';");
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
				$chunk_id = db_nextval("chunks", "chunk_id");

				//keep a record of all the chunks we've created
				$chunk_ids[] = $chunk_id;
	
				//create the chunk
				runq("INSERT INTO chunks (chunk_id, transform_id) VALUES ('".db_escape($chunk_id)."', '".db_escape($this->transform_id)."');");
	
				//add a chunk's worth of member ids to the chunk
				runq("INSERT INTO chunk_member_ids SELECT DISTINCT {$chunk_id} AS chunk_id, ".db_cast_bigint("customerid")." AS member_id FROM dump_{$this->extract_id}_customer WHERE custtypeid='INDI' ORDER BY ".db_cast_bigint("customerid")." ASC LIMIT ".Conf::$chunk_size." OFFSET ".$chunk_offset.";");

			} catch (Exception $e) {
				print_r($e->getMessage());
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