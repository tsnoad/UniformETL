#!/usr/bin/php5
<?php

/**
The Useful:
http://www.howtoforge.com/install-and-configure-openldap-on-ubuntu-karmic-koala
http://www.howforge.com/how-create-and-verify-ssha-hash-using-php
*/

#ldapsearch -x -H ldap://localhost -b dc=home,dc=local

$timer = microtime(true);

$base = "dc=example,dc=com";

ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

$ldap = ldap_connect("localhost");
/* var_dump($ldap); */

$bind = ldap_bind($ldap, "cn=admin,".$base, "example");
/* var_dump($bind); */

$iterations = 5;

for ($i = 1; $i <= $iterations; $i ++) {
	$searchids[] = $i;
}

if (true) {
	$search = ldap_search($ldap, $base, "(|(uid=".implode(")(uid=", $searchids)."))", array("uid", "cn", "userpassword", "objectclass"));
	$search_result = ldap_get_entries($ldap, $search);

/* 	print_r($search_result); */
/* 	var_dump($search_result['count']); */

	if (true) {
		for ($i = 1; $i <= $iterations; $i ++) {
			ldap_delete($ldap, "uid={$i},".$base);
		}
	}
	
	if (true) {
		for ($i = 1; $i <= $iterations; $i ++) {
			$add['uid'] = $i;
			$add['objectclass'][0] = "inetOrgPerson";
			
			$add['cn'] = " ";
			$add['sn'] = "seven";
			$add['givenname'] = "gary";
			$add['mail'] = "seven@example.com";
	
	
	/* 		$add['userpassword'] = "{MD5}7xhh8SlK65OgifmSQATEQg=="; */
	/* 		$add['userpassword'] = "squiggles"; */
	
			$salt = md5(rand());
			$add['userpassword'] = "{SSHA}".base64_encode(pack("H*",sha1("squiggles".$salt)).$salt);
			
			ldap_add($ldap, "uid={$i},".$base, $add);
		}
	}
}

if (false) {
	$search = ldap_search($ldap, $base, "(cn=*)", array("uid", "cn", "userpassword", "objectclass"));
	$search_result = ldap_get_entries($ldap, $search);
	$search_result = array_slice($search_result, 0, 100000);

	foreach ($search_result as $search_result_item_key => $search_result_item) {
		if ($search_result_item_key == "count") continue;
	
		var_dump($search_result_item['uid'][0]);
	
		ldap_delete($ldap, "uid={$search_result_item['uid'][0]},".$base);
	}
}





var_dump(microtime(true) - $timer);

?>