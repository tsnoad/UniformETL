#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/autoload.php");

$source_path = $_SERVER["argv"][1];
$source_timestamp = $_SERVER["argv"][2];
$source_md5 = $_SERVER["argv"][3];

$extract = New ExtractFullStaging;
$extract->start($source_path, $source_timestamp, $source_md5);

?>