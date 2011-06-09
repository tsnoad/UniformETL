<?php

Class Plugins {
	public static $plugins = array(
		array("models_required-transforms", "MemberIds", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberAddresses", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberConfluenceStatuses", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberDivisions", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberEcpdStatuses", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberEmails", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberGrades", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberInvoices", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberNames", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberPasswords", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberPersonals", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberReceipts", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberWebStatuses", "hook_models_required_transforms"),

		array("models_required-tables", "MemberIds", "hook_models_required_tables"),
		array("models_required-tables", "MemberAddresses", "hook_models_required_tables"),

		array("models_transform-priority", "MemberIds", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberAddresses", "hook_models_transform_priority"),

		array("models_dump-table-source", "MemberIds", "hook_models_dump_table_source"),
		array("models_dump-table-source", "MemberAddresses", "hook_models_dump_table_source"),

		array("transform_deleted-members-query", "Transform", "hook_transform_deleted_members_query"),

		array("api_get-users_singles", "MemberIds", "hook_api_get_member"),
		array("api_get-users_singles", "MemberPersonals", "hook_api_get_member"),
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
/* 				var_dump(class_exists($plugin[1])); */

/* 				var_dump(method_exists($plugin[1], $plugin[1])); */

				$version = explode(".", phpversion());

				if ($version[0] == "5" && $version[1] >= "3") {
					$plugin_return[$plugin[1]] = call_user_func_array($plugin[1]."::".$plugin[2], array($data));

				} else {
/* 					$model = New $plugin[1]; */
					$plugin_return[$plugin[1]] = call_user_func_array(array($plugin[1], $plugin[2]), array($data));
				}

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