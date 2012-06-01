#!/usr/bin/php5
<?php

require_once("/etc/uniformetl/autoload.php");

$reporter = New Reporter;
$reporter->report_history();

?>