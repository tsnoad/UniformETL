<?php

require_once("/etc/uniformetl/autoload.php");

Class Plugins {
	public static $plugins = array(
		array("models_required-transforms", "MemberIds", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberAddresses", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberColleges", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberConfluenceStatuses", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberDivisions", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberEcpdStatuses", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberEmails", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberGrades", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberInvoiceItems", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberInvoices", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberNames", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberPasswords", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberPersonals", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberReceiptAllocations", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberReceipts", "hook_models_required_transforms"),
		array("models_required-transforms", "MemberWebStatuses", "hook_models_required_transforms"),

		array("models_required-tables", "MemberIds", "hook_models_required_tables"),
		array("models_required-tables", "MemberAddresses", "hook_models_required_tables"),
		array("models_required-tables", "MemberColleges", "hook_models_required_tables"),
		array("models_required-tables", "MemberConfluenceStatuses", "hook_models_required_tables"),
		array("models_required-tables", "MemberDivisions", "hook_models_required_tables"),
		array("models_required-tables", "MemberEcpdStatuses", "hook_models_required_tables"),
		array("models_required-tables", "MemberEmails", "hook_models_required_tables"),
		array("models_required-tables", "MemberGrades", "hook_models_required_tables"),
		array("models_required-tables", "MemberInvoiceItems", "hook_models_required_tables"),
		array("models_required-tables", "MemberInvoices", "hook_models_required_tables"),
		array("models_required-tables", "MemberNames", "hook_models_required_tables"),
		array("models_required-tables", "MemberPasswords", "hook_models_required_tables"),
		array("models_required-tables", "MemberPersonals", "hook_models_required_tables"),
		array("models_required-tables", "MemberReceiptAllocations", "hook_models_required_tables"),
		array("models_required-tables", "MemberReceipts", "hook_models_required_tables"),
		array("models_required-tables", "MemberWebStatuses", "hook_models_required_tables"),

		array("models_transform-priority", "MemberIds", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberAddresses", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberColleges", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberConfluenceStatuses", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberDivisions", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberEcpdStatuses", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberEmails", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberGrades", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberInvoiceItems", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberInvoices", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberNames", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberPasswords", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberPersonals", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberReceiptAllocations", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberReceipts", "hook_models_transform_priority"),
		array("models_transform-priority", "MemberWebStatuses", "hook_models_transform_priority"),

		array("transform_deleted-members-query", "ExtractFullPlugins", "hook_transform_deleted_members_query"),
		array("transform_deleted-members-query", "ExtractLatestPlugins", "hook_transform_deleted_members_query"),

/* 		array("extract-daemon", "ExtractFullLauncher", "start"), */

		array("extract_index-sql", "MemberIds", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberAddresses", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberColleges", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberConfluenceStatuses", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberDivisions", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberEcpdStatuses", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberEmails", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberGrades", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberInvoiceItems", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberInvoices", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberNames", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberPasswords", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberPersonals", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberReceiptAllocations", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberReceipts", "hook_extract_index_sql"),
		array("extract_index-sql", "MemberWebStatuses", "hook_extract_index_sql"),

		array("transform_update", "PluginHistory", "record_update"),

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

				//PHP < 5.3 mode
				} else {
					$plugin_return[$plugin[1]] = call_user_func_array(array($plugin[1], $plugin[2]), array($data));
				}

				if ($location == "transform_deleted-members-query") {
					$data = $plugin_return[$plugin[1]];
				}
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