#!/bin/bash

for i in {1..60}
do
	processes=`psql -At -c "SELECT watch_pid FROM processes WHERE finished!=TRUE;" hotel`

	#echo $processes
	
	process_count=`echo $processes | wc -w`
	#echo $process_count

	for i in $(seq 1 $process_count)
	do
		#echo  $i' squiggles'
		process=`echo $processes | cut -d " " -f 1`
		ps u $process
	done

	sleep 1s
done