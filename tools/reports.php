<?php

function runq($query) {
	$conn = pg_connect("host=localhost dbname=hotel user=user password=skoobar");
	$result = pg_query($conn, $query);
	$return = pg_fetch_all($result);
	pg_close($conn);

	return $return;
}

$foo = runq("select g.grade, d.division, count(m.member_id) from member_ids m inner join grades g on (g.member_id=m.member_id) inner join divisions d on (d.member_id=m.member_id) group by g.grade, d.division order by g.grade, d.division;");

foreach ($foo as $bar) {
	$x = $bar['division'];
	$y = $bar['grade'];

	$z = $bar['count'];

	$x_y[$x][$y] = $z;
	$y_x[$y][$x] = $z;
}

$x_totals = array_map("array_sum", $x_y);
$y_totals = array_map("array_sum", $y_x);

array_multisort($x_totals, $x_y);
array_multisort($y_totals, $y_x);

$total = array_sum(array_map("array_sum", $x_y));

$max = max(array_map("max", $x_y));

?>
<html>
<head>
<style>
html, body {
	margin: 0px;
}
table {
	width: 100%;
	border: 0px solid rgba(0, 0, 0, 0.125);
	border-width: 0px 1px 1px 0px;
}
tr {
}
td {
	width: 5%;
	padding: 5px;
	border: 0px solid rgba(0, 0, 0, 0.125);
	border-width: 1px 0px 0px 1px;
}
tr.headrow td.headcell {
	background-color: rgba(0, 0, 0, 0.125);
	font-family: Arial; text-align: center; font-size: 8pt; font-weight: bold;
}
tr.headrow td.totalcell {
	border-left: 1px solid rgba(0, 0, 0, 0.5);
}
tr.datarow td.headcell {
	background-color: rgba(0, 0, 0, 0.125);
	font-family: Arial; text-align: right; font-size: 8pt; font-weight: bold;
}
tr.datarow td.datacell {
}
tr.datarow td.totalcell {
	border-left: 1px solid rgba(0, 0, 0, 0.5);
}
tr.totalrow td.headcell {
	background-color: rgba(0, 0, 0, 0.125);
	font-family: Arial; text-align: right; font-size: 8pt; font-weight: bold;
}
tr.totalrow td.totalcell {
	border-top: 1px solid rgba(0, 0, 0, 0.5);
}
div.datarow1 {
	font-family: Georgia; text-align: right; font-size: 9pt;
}
div.datarow2 {
	font-family: Georgia; text-align: right; font-size: 8pt; color: rgba(0, 0, 0, 0.5);
}

</style>
</head>
<body>
<table cellpadding="0" cellspacing="0">
	<tr class="headrow">
		<td class="headcell"></td>
		<? foreach (array_keys($x_y) as $x_key): ?>
			<td class="headcell"><?= $x_key ?></td>
		<? endforeach; ?>
		<td class="headcell totalcell">Total</td>
	</tr>

	<? foreach (array_keys($y_x) as $y_key): ?>
		<tr class="datarow">
			<td class="headcell"><?= $y_key ?></td>
			<? foreach (array_keys($x_y) as $x_key): ?>
				<td class="datacell" style="background-color: rgba(0, 255, 0, <?= round($x_y[$x_key][$y_key] / $max, 2) ?>)">
					<div class="datarow1"><?= (float)$x_y[$x_key][$y_key] ?></div>
					<div class="datarow2"><?= round($x_y[$x_key][$y_key] / $total, 4) * 100 ?>%</div>
				</td>
			<? endforeach; ?>
			<td class="totalcell" style="background-color: rgba(0, 255, 0, <?= round(array_sum($y_x[$y_key]) / max($y_totals), 2) ?>)">
				<div class="datarow1"><?= array_sum($y_x[$y_key]) ?></div>
				<div class="datarow2"><?= round(array_sum($y_x[$y_key]) / $total, 4) * 100 ?>%</div>
			</td>
		</tr>
	<? endforeach; ?>

	<tr class="totalrow">
		<td class="headcell totalcell">Total</td>
		<? foreach (array_keys($x_y) as $x_key): ?>
			<td class="totalcell" style="background-color: rgba(0, 255, 0, <?= round(array_sum($x_y[$x_key]) / max($x_totals), 2) ?>)">
				<div class="datarow1"><?= array_sum($x_y[$x_key]) ?></div>
				<div class="datarow2"><?= round(array_sum($x_y[$x_key]) / $total, 4) * 100 ?>%</div>
			</td>
		<? endforeach; ?>
		<td>&nbsp;</td>
	</tr>
</table>
</body>
</html>
<?
?>