#!/usr/bin/php5
<?php

$timer = microtime(true);

$ldap = ldap_connect("localhost");
var_dump($ldap);

$bind = ldap_bind($ldap, "cn=admin,dc=home,dc=com", "1234");
var_dump($bind);

$search = ldap_search($ldap, "dc=home,dc=com", "uid=*");
var_dump($bind);

$search_result = ldap_get_entries($ldap, $search);

var_dump($search_result);

var_dump(microtime(true) - $timer);

?>