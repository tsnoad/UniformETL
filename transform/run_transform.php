<?php

require_once("/etc/uniformetl/autoload.php");

$transform = New Transform;
$transform->extract_id = $_SERVER['argv'][1];
$transform->init_transform();

?>