<?php

Class Plugins {
	public static $plugins = array(
		array("transform_deleted-members-query", "Transform", "hook_transform_deleted_members_query"),
		array("api_get-users_singles", "MemberIds", "hook_api_get_member"),
		array("api_get-users_singles", "MemberWebStatuses", "hook_api_get_member"),
		array("api_get-users_singles", "MemberEcpdStatuses", "hook_api_get_member"),
		array("api_get-users_singles", "MemberGrades", "hook_api_get_member"),
		array("api_get-users_singles", "MemberDivisions", "hook_api_get_member"),
		array("api_get-users_plurals", "MemberNames", "hook_api_get_member_plurals"),
		array("api_get-users_plurals", "MemberEmails", "hook_api_get_member_plurals"),
		array("api_get-users_plurals", "MemberAddresses", "hook_api_get_member_plurals")
	);

	function hook($location, $data) {
/* 		var_dump("hook: ".$location); */

		foreach (self::$plugins as $plugin) {
			if ($plugin[0] == $location) {
/* 				var_dump($plugin[1]."::".$plugin[2]); */

/* 				var_dump(class_exists($plugin[1])); */

/* 				var_dump(method_exists($plugin[1], $plugin[1])); */

				$plugin_return[$plugin[1]] = call_user_func_array($plugin[1]."::".$plugin[2], array($data));

				if ($location == "transform_deleted-members-query") break;
			}
		}


		if ($location == "transform_deleted-members-query") {
			return $data;

		} else {
			return $plugin_return;
		}
	}
}

?>