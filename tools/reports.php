<?php

require_once("/etc/uniformetl/autoload.php");
require_once("/etc/uniformetl/database.php");



/* $foo = runq("select d.division as x, g.grade as y, count(m.member_id) as z from member_ids m inner join grades g on (g.member_id=m.member_id) inner join divisions d on (d.member_id=m.member_id) group by g.grade, d.division order by g.grade, d.division;"); */
/* $foo = runq("select substr(email, strpos(email, '@') + 1) as x, 0 as y, count(email) as z from emails group by substr(email, strpos(email, '@') + 1);"); */


$conn = pg_connect("host=".Conf::$dbhost." port=5432 dbname=".Conf::$dbname." user=".Conf::$dbuser." password=".Conf::$dbpass."");
pg_query($conn, "create temp view member_receipts as select member_id, sum(amount) from receipts group by member_id;");
pg_query($conn, "create temp view member_invoices as select member_id, sum(amount) from invoices group by member_id;");
$result = pg_query($conn, "select r.member_id, r.sum-i.sum from member_receipts r, member_invoices i where i.member_id=r.member_id limit 100;");
$return = pg_fetch_all($result);
pg_close($conn);

print_r($return);

die();

foreach ($foo as $bar) {
	$x = $bar['x'];
	$y = $bar['y'];

	$z = $bar['z'];

	$x_y[$x][$y] = $z;
	$y_x[$y][$x] = $z;
}


$x_totals = array_map("array_sum", $x_y);
$y_totals = array_map("array_sum", $y_x);

array_multisort($x_totals, $x_y);
array_multisort($y_totals, $y_x);

$total = array_sum(array_map("array_sum", $x_y));

$max = max(array_map("max", $x_y));

$x_y = array_slice($x_y, -10, 10, true);
$y_x = array_slice($y_x, -10, 10, true);

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
<?

/*
unset($x, $y, $x_y, $y_x, $x_totals, $y_totals, $total, $max);

$scale = 2;
$zoom = pow(10, $scale);

var_dump($zoom);

$zoom *= .5;

$foo = runq("SELECT round(p.latitude::numeric, {$scale}) as latitude, round(p.longitude::numeric, {$scale}) as longitude, count(a.member_id) FROM addresses a INNER JOIN postcode_geolocation p ON (p.postcode=a.postcode) GROUP BY round(p.latitude::numeric, {$scale}), round(p.longitude::numeric, {$scale});");

foreach ($foo as $bar) {
	$x = $bar['longitude'];
	$y = $bar['latitude'];

	$z = $bar['count'];

	$x_y[$x][$y] = $z;
	$y_x[$y][$x] = $z;
}

$total = array_sum(array_map("array_sum", $x_y));

$max = max(array_map("max", $x_y));

$min_lon = min(array_keys($x_y));
$max_lon = max(array_keys($x_y));

$min_lat = min(array_keys($y_x));
$max_lat = max(array_keys($y_x));

$lon_range = $max_lon - $min_lon;
$lat_range = $max_lat - $min_lat;
*/

?>
<div style="width: <?= (($lon_range + 1) * $zoom) ?>px; height: <?= (($lat_range + 1) * $zoom) ?>px; position: relative; border: 1px solid black;">
<?

/*
foreach ($foo as $bar) {
	$lon = $bar['longitude'];
	$lon -= $min_lon;
	$lon *= $zoom;

	$lat = $bar['latitude'];
	$lat += $max_lat * -1;
	$lat *= -1;
	$lat *= $zoom;

	?>
	<div style="width: <?= $zoom / 10 ?>px; height: <?= $zoom / 10 ?>px; position: absolute; left: <?= $lon ?>px; top: <?= $lat ?>px; background-color: rgba(0, 0, 0, <?= ($bar['count'] / $max + .1) ?>);"></div>
	<?
}
*/

?>
</div>
</body>
</html>
<?

?>