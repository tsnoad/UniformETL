#!/usr/bin/php5
<?php

#ldapsearch -x -H ldap://localhost -b dc=home,dc=local

$timer = microtime(true);

ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

$ldap = ldap_connect("localhost");
/* var_dump($ldap); */

$bind = ldap_bind($ldap, "cn=admin,dc=home,dc=local", "admin");
var_dump($bind);

for ($i = 1; $i <= 1000; $i ++) {
	$searchids[] = $i;
}

$search = ldap_search($ldap, "dc=home,dc=local", "(|(uid=".implode(")(uid=", $searchids)."))", array("uid", "cn", "userpassword", "objectclass"));
/* var_dump($search); */

$search_result = ldap_get_entries($ldap, $search);
/* print_r($search_result); */
print_r($search_result['count']);



/*
for ($i = 1; $i <= 1000; $i ++) {
	$add['uid'] = $i;
	$add['objectclass'][0] = "inetOrgPerson";
	
	$add['cn'] = "gary seven";
	$add['sn'] = "seven";
	$add['givenname'] = "gary";
	$add['mail'] = "seven@example.com";
	$add['userpassword'] = "squiggles";
	
	ldap_add($ldap, "uid={$i},ou=people,dc=home,dc=local", $add);
}
*/

var_dump(microtime(true) - $timer);

?>