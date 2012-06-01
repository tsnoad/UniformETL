#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/autoload.php");

$extract = New ExtractLatestStaging;
$extract->start();

?>