#!/usr/bin/php5
<?php

/**
The Useful:
http://www.howtoforge.com/install-and-configure-openldap-on-ubuntu-karmic-koala
http://www.howforge.com/how-create-and-verify-ssha-hash-using-php
https://help.ubuntu.com/10.04/serverguide/C/openldap-server.html

SLES:
http://www.openldap.org/doc/admin24/quickstart.html
http://www.server-world.info/en/note?os=SUSE_Linux_Enterprise_Server_11&p=ldap
*/

#ldapsearch -x -H ldap://localhost -b dc=home,dc=local
#ldapsearch -x -D "cn=Manager,dc=my-domain,dc=com" -w "resources" -h "192.168.13.189" -b "dc=my-domain,dc=com" "uid=3900160"
#ldapsearch -x -D "cn=admin,dc=ieaust,dc=com" -w "" -h "localhost" -b "dc=ieaust,dc=com" "cn=admin"
#ldapsearch -x -D "cn=admin,dc=example,dc=com" -w "" -h "localhost" -b "dc=example,dc=com" "cn=admin"
#ldapsearch -x -D "cn=Manager,dc=my-domain,dc=com" -w "" -h "localhost" -b "dc=my-domain,dc=com" "cn=Manager"

$timer = microtime(true);

/* $base = "dc=example,dc=com"; */
$base = "dc=my-domain,dc=com";

ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

/* $ldap = ldap_connect("localhost"); */
$ldap = ldap_connect("192.168.13.189");
var_dump($ldap);

/* $bind = ldap_bind($ldap, "cn=admin,".$base, "example"); */
$bind = ldap_bind($ldap, "cn=Manager,".$base, "resources");
var_dump($bind);

/*
$iterations = 5;

for ($i = 1; $i <= $iterations; $i ++) {
	$searchids[] = $i;
}
*/

$searchids = array("3359780");

var_dump($searchids);

if (true) {
	$search = ldap_search($ldap, $base, "(|(uid=".implode(")(uid=", $searchids)."))", array("uid", "cn", "userpassword", "objectclass"));
	$search_result = ldap_get_entries($ldap, $search);

	print_r($search_result);
	var_dump($search_result['count']);

	if (false) {
		for ($i = 1; $i <= $iterations; $i ++) {
			ldap_delete($ldap, "uid={$i},".$base);
		}
	}
	
	if (false) {
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