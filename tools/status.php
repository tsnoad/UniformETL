<?php

/*
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

*/

$log = file_get_contents("/etc/uniformetl/logs/transformlog");

$log_lines = explode("\n", $log);

/* $log_lines = array_map(create_function('$a', 'return substr($a, 0, 80);'), $log_lines); */

$log = implode("\n", array_slice($log_lines, -40, 40));

?>

<!DOCTYPE html>
<html>
	<head>
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js" type="text/javascript"></script>
<script>
$(function() {
	var timer, counter = 0;
	
	// Update countdown number every second... and count down
	timer = setInterval(function() {

		counter ++;

		var minutes = Math.floor(counter / (60*10));
		var seconds = Math.floor((counter / 10) - (minutes * 60)).toString();
		var second_fracs = Math.round(counter - (minutes * 60 * 10) - (seconds * 10));

		if (seconds.length < 2) {
			seconds = "0"+seconds;
		}
	
		$("#countup").html(minutes+":"+seconds+"."+second_fracs);

		$(".progressbar_prog").css("width", (counter / 10)+"px");

		if (counter > 1500) { clearInterval(timer)};
	}, 100);
});

$(function() {
	var timer, counter = 9000;
	
	// Update countdown number every second... and count down
	timer = setInterval(function() {

		counter --;

		var minutes = Math.floor(counter / (60*10));
		var seconds = Math.floor((counter / 10) - (minutes * 60)).toString();
		var second_fracs = Math.round(counter - (minutes * 60 * 10) - (seconds * 10));

		if (seconds.length < 2) {
			seconds = "0"+seconds;
		}
	
		$("#countdown").html(minutes+":"+seconds+"."+second_fracs);

		if (counter == 0) {clearInterval(timer)};
	}, 100);

	$("#messages pre").scrollTop($("#messages pre")[0].scrollHeight);

	flashtimer = setInterval(function() {
		$("body").toggleClass("flashon");
	}, 667);
});
</script>
<style>
	html, body {
		padding: 30px;
		background-color: black;
		color: #e5fdff;
		font-family: "lucida grande", Lucinda Grande, Lucinda Sans, Lucinda,tahoma,verdana,arial,sans-serif;
		font-size: 9pt;
		font-weight: bold;
	}
	h1, h2 {
		margin: 0px;
		font-size: 9pt;
		font-weight: bold;
	}
	h1 {
		color: #727e7f;
	}
	.hr {
		margin: 10px 0px;
		color: #727e7f;
	}
	.progressbar {
		width: 120px;
		height: 10px;
		margin: 10px 0px;
		border: 2px solid #393f40;
		border-radius: 5px;
	}
	.progressbar_prog {
		height: 6px;
		margin: 2px;
		border-radius: 2.5px;
		background-color: #acbebf;
	}
	#timer_elapsed {
		margin-top: 20px;
		color: #727e7f;
	}
	#timer_remaining {
		margin-top: 10px;
	}
	#messages {
		width: 360px;
		height: 240px;
		margin: 20px 0px;
		padding: 4px 8px;
		border: 2px solid #393f40;
		border-radius: 2.5px;
		font-family: monospace;
		font-size: 7pt;
	}
	#messages pre {
		width: 360px;
		height: 240px;
		margin: 0px;
		overflow: hidden;
	}
	.prevetl {
		margin: 5px 0px;
	}
	.prevetl_time {
		color: #727e7f;
	}
	.prevetl_failed {
		padding: 1px 4px;
	}
	body.flashon .prevetl_failed {
		background-color: #e5fdff;
		color: black;
	}
</style>
	</head>
	<body>
		<h1>_ Uniform ETL</h1>

		<div class="hr">-----</div>

		<h2>ETL in progress:</h2>

		<div class="progressbar">
			<div class="progressbar_prog" style="width: 46px;"></div>
		</div>

		<div id="timer_elapsed"><span style="visibility: hidden;">/</span> <span id="countup">0</span> elapsed<!--
</div>
		<div id="timer_remaining">
-->/ <span id="countdown">1500</span> remaining</div>

		<div id="messages">
			<pre><?= $log ?></pre>
		</div>

		<div class="prevetl">2011-08-18 <span class="prevetl_time">23:45:30</span></div>
		<div class="prevetl">2011-08-18 <span class="prevetl_time">23:45:30</span></div>
		<div class="prevetl">2011-08-18 <span class="prevetl_time">23:45:30</span></div>
		<div class="prevetl">2011-08-18 <span class="prevetl_time">23:45:30</span> <span class="prevetl_failed">FAILED</span></div>
		<div class="prevetl">2011-08-18 <span class="prevetl_time">23:45:30</span></div>
		<div class="prevetl">2011-08-18 <span class="prevetl_time">23:45:30</span></div>
		<div class="prevetl">2011-08-18 <span class="prevetl_time">23:45:30</span> <span class="prevetl_failed">FAILED</span></div>
		<div class="prevetl">2011-08-18 <span class="prevetl_time">23:45:30</span> <span class="prevetl_failed">FAILED</span></div>
		<div class="prevetl">2011-08-18 <span class="prevetl_time">23:45:30</span></div>
		<div class="prevetl">2011-08-18 <span class="prevetl_time">23:45:30</span></div>
	</body>
</html>