<?php

function runq($query) {
	$conn = pg_connect("host=localhost dbname=hotel user=user password=skoobar");
	$result = pg_query($conn, $query);
	$return = pg_fetch_all($result);
	pg_close($conn);

	return $return;
}

$processes = runq("SELECT * FROM processes WHERE finished!=TRUE;");

foreach ($processes as $process_key => $process) {
	$ps_query = shell_exec("ps u ".escapeshellarg($process['watch_pid']));
	$ps_rows = explode("\n", trim($ps_query));
	array_shift($ps_rows);
	$processes[$process_key]['wps'] = $ps_rows[0];

	$ps_query = shell_exec("ps u ".escapeshellarg($process['extract_pid']));
	$ps_rows = explode("\n", trim($ps_query));
	array_shift($ps_rows);
	$processes[$process_key]['eps'] = $ps_rows[0];
}



?>

<html>
	<head>
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js" type="text/javascript"></script>
	</head>
	<body onload="
window.document.getElementById('spinnerneedle').style.webkitTransform = 'rotate(3600deg)';
window.document.getElementById('spinnerneedlequad1').style.webkitTransform = 'rotate(-3600deg)';
window.document.getElementById('spinnerneedlequad2').style.webkitTransform = 'rotate(-3600deg)';
" style="margin: 0px;">
		<? if (empty($processes)): ?>
				<div style="margin: 20px 0px;">
					<div style="font-family: Helvetica; font-size: 30pt; font-weight: bold; text-align: center;">No Processes</div>
				</div>
		<? else: ?>
			<? foreach ($processes as $process): ?>
				<div style="height: 40%; position: relative;">
					<div style="position: relative; margin: 0px 0px; color: #333333; font-family: Helvetica Neue; font-size: 60pt; font-weight: bold; letter-spacing: -4pt; text-align: center;">
<div style="width: 100%; position: absolute; left: 0px; color: transparent; text-shadow: rgba(255, 255, 255, 0.5) 0px 5px 10px;"><span style="visibility: hidden;">...</span>Processing...</div>

<span style="visibility: hidden;">...</span>Processing...</div>

					<div style="margin: 0px 0px; font-family: Helvetica Neue; font-size: 60pt; font-weight: bold; letter-spacing: -4pt; text-align: center;"></div>


<!--
					<div style="width: 80%; margin: 10px auto;">
						<div style="width: 25%; float: left;">
							<div style="margin: 0px 10px; padding: 30px 0px 5px 0px; background-color: #4e9a06; color: white; font-family: Helvetica Neue; font-size: 15pt; font-weight: bold; letter-spacing: -1pt; text-align: center;">foobar</div>
						</div>
						<div style="width: 25%; float: left;">
							<div style="margin: 0px 10px; padding: 30px 0px 5px 0px; background-color: #4e9a06; color: white; font-family: Helvetica Neue; font-size: 15pt; font-weight: bold; letter-spacing: -1pt; text-align: center;">foobar</div>
						</div>
						<div style="width: 25%; float: left;">
							<div style="margin: 0px 10px; padding: 30px 0px 5px 0px; background-color: #4e9a06; color: white; font-family: Helvetica Neue; font-size: 15pt; font-weight: bold; letter-spacing: -1pt; text-align: center;">foobar</div>
						</div>
						<div style="width: 25%; float: left;">
							<div style="margin: 0px 10px; padding: 30px 0px 5px 0px; background-color: #4e9a06; color: white; font-family: Helvetica Neue; font-size: 15pt; font-weight: bold; letter-spacing: -1pt; text-align: center;">foobar</div>
						</div>
						<div style="clear: both;"></div>
					</div>
-->


					<div style="width: 100px; height: 100px; position: relative; overflow: hidden; margin: 10px auto; -webkit-border-radius: 50%; -webkit-box-shadow: inset 0px 5px 10px rgba(0, 0, 0, 0.75);">
						<div id="spinnerneedle" style="width: 100%; height: 100%; position: absolute; left: 0%; top: 0%; -webkit-transform: rotate(0deg); -webkit-transition: all 30000 linear;">

							<div style="width: 50%; height: 50%; position: absolute; left: 0%; top: 0%; overflow: hidden;">
								<div id="spinnerneedlequad1" style="width: 200%; height: 200%; position: absolute; left: 0%; top: 0%; background-color: #4e9a06; -webkit-border-radius: 50%; -webkit-box-shadow: inset 0px 5px 10px rgba(0, 0, 0, 0.75); -webkit-transform: rotate(0deg); -webkit-transition: all 30000 linear;"></div>
							</div>

							<div style="width: 50%; height: 50%; position: absolute; left: 50%; top: 0%; overflow: hidden;">
								<div id="spinnerneedlequad2" style="width: 200%; height: 200%; position: absolute; left: -100%; top: 0%; background-color: #4e9a06; -webkit-border-radius: 50%; -webkit-box-shadow: inset 0px 5px 10px rgba(0, 0, 0, 0.75); -webkit-transform: rotate(0deg); -webkit-transition: all 30000 linear;"></div>
							</div>

						</div>

						<div style="width: 50%; height: 50%; position: absolute; left: 25%; top: 25%; background-color: white; -webkit-border-radius: 50%; -webkit-box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.75);"></div>
					</div>

<!-- 					<div style="margin: 10px 0px; font-family: Helvetica; font-size: 15pt; text-align: center;">Started <?= $process['start_date']; ?></div> -->
<!-- 					<div style="margin: 10px 0px; font-family: Helvetica; font-size: 10pt; text-align: center;">With Data From <?= $process['source_timestamp']; ?></div> -->
					<div style="margin: 10px 0px; font-family: Helvetica; font-size: 15pt; text-align: center;">Process <?= $process['pid']; ?></div>
					<div style="margin: 10px 0px; font-family: Helvetica; font-size: 15pt; text-align: center;">Process <?= $process['wps']; ?></div>
					<div style="margin: 10px 0px; font-family: Helvetica; font-size: 15pt; text-align: center;">Process <?= $process['eps']; ?></div>


					<div style="width: 100%; height: 10px; position: absolute; left: 0px; bottom: 0px; background-color: #888a85; -webkit-box-shadow: inset 0px 5px 10px rgba(0, 0, 0, 0.75);"></div>
				</div>

			<? endforeach; ?>
		<? endif; ?>
	</body>
</html>