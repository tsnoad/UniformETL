<?php

$handle = fopen("https://192.168.25.161/users/100001?api_key=3CEaCHxr8IoTD0NzEpLeGdj6iWRnOr2", "r");

print_r(stream_get_meta_data($handle));

$contents = fread($handle, 1024);
var_dump($contents);

?>