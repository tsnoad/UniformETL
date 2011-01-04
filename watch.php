#!/usr/bin/php5
<?php

$dump_files = trim(shell_exec("ssh -i /home/user/.ssh/id_rsa_foxrep easysadmin@foxrep.nat.internal 'ls -l --time-style=+%s /data01/datadump/*.tgz' | awk '{ print $6,$7; }'"));




$remote_time = trim(shell_exec("ssh -i /home/user/.ssh/id_rsa_foxrep easysadmin@foxrep.nat.internal 'date +%s'"));

$local_time = time();

$time_difference = $local_time - $remote_time;

//$time_difference = $local_time - $remote_time;
//$local_time = $remote_time + $time_difference;
//$remote_time = $local_time - $time_difference;

/* print_r($dump_files); */




$dump_file_time_remote_time = trim(preg_replace("/\/data01\/datadump\/.+\.tgz$/", "", $dump_files));

$dump_file_time_local_time = $dump_file_time_remote_time + $time_difference;

var_dump(date("Y-m-d H:i:s", $dump_file_time_local_time));




preg_match("/\/data01\/datadump\/.+\.tgz$/", $dump_files, $dump_file_path);

$dump_file_path = basename($dump_file_path[0]);

var_dump($dump_file_path);




$dump_file_md5_query = trim(shell_exec("ssh -i /home/user/.ssh/id_rsa_foxrep easysadmin@foxrep.nat.internal 'md5sum /data01/datadump/{$dump_file_path}'"));

preg_match("/^[0-9a-z]{32,32}/", $dump_file_md5_query, $dump_file_md5);

var_dump($dump_file_md5[0]);




if ($dump_file_time_local_time > time() + 60 * 5) {
	die("dump file is too new");
}

if (false) {
	die("dump file has already been used");
}




/* mkdir("/home/user/hotel/dumps/".$dump_file_time_local_time); */

/* var_dump(trim(shell_exec("ssh -i /home/user/.ssh/id_rsa_foxrep easysadmin@foxrep.nat.internal 'ls -l --time-style=long-iso /data01/datadump/*.tgz'"))); */

/* var_dump(trim(shell_exec("ssh -i /home/user/.ssh/id_rsa_foxrep easysadmin@foxrep.nat.internal 'md5sum /data01/datadump/*.tgz'"))); */

?>