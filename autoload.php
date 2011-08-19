<?php

/**
 * Autoload Function
 *
 * Loads classes automatically, instead of us having to call include().
 * Not used everywhere yet...
 *
 */

function __autoload($class_name) {
	switch ($class_name) {
		case "Conf":
		    require_once("/etc/uniformetl/config.php");
			break;

		case "Plugins":
		    require_once("/etc/uniformetl/".strtolower($class_name).".php");
			break;

		case "Janitor":
		    require_once("/etc/uniformetl/janitor/janitor.php");
			break;

		case "ExtractFull":
		    require_once("/etc/uniformetl/extract/extractors/full/extract.php");
			break;
		case "ExtractFullLauncher":
		    require_once("/etc/uniformetl/extract/extractors/full/extract_launcher.php");
			break;
		case "ExtractFullPlugins":
		    require_once("/etc/uniformetl/extract/extractors/full/extract_full_plugins.php");
			break;
		case "ExtractFullGetColumns":
		    require_once("/etc/uniformetl/extract/extractors/full/get_columns.php");
			break;

		case "ExtractLatest":
		    require_once("/etc/uniformetl/extract/extractors/latest/extract_launcher.php");
			break;
		case "ExtractLatestPlugins":
		    require_once("/etc/uniformetl/extract/extractors/latest/extract_latest_plugins.php");
			break;

		case "TransformLauncher":
		    require_once("/etc/uniformetl/transform/transform_launcher.php");
			break;

		case "SingleTransforms":
		case "PluralTransforms":
		case "Chunks":
		case "GlobalTiming":
		    require_once("/etc/uniformetl/transform/".strtolower($class_name).".php");
			break;

		case "Models":
		    require_once("/etc/uniformetl/transform/transform_models.php");
			break;

		case "MemberIds":
		case "MemberPersonals":
		case "MemberPasswords":
		case "MemberSecretQuestions":
		case "MemberNames":
		case "MemberEmails":
		case "MemberAddresses":
		case "MemberInvoiceItems":
		case "MemberInvoices":
		case "MemberReceiptAllocations":
		case "MemberReceipts":
		case "MemberGrades":
		case "MemberDivisions":
		case "MemberColleges":
		case "MemberSocieties":
		case "MemberStatuses":
		    require_once("/etc/uniformetl/transform/transform_models/member_".strtolower(substr($class_name, 6)).".php");
			break;
		case "MemberWebStatuses":
		    require_once("/etc/uniformetl/transform/transform_models/member_web_statuses.php");
			break;
		case "MemberEcpdStatuses":
		    require_once("/etc/uniformetl/transform/transform_models/member_ecpd_statuses.php");
			break;
		case "MemberEpdpStatuses":
		    require_once("/etc/uniformetl/transform/transform_models/member_epdp_statuses.php");
			break;
		case "MemberConfluenceStatuses":
		    require_once("/etc/uniformetl/transform/transform_models/member_confluence_statuses.php");
			break;

		case "PluginHistory":
		    require_once("/etc/uniformetl/plugins/history.php");
			break;

		default:
			if (strpos($class_name, "APIModel") === 0) {
			    require_once("/etc/uniformetl/api/apimodel_".strtolower(substr($class_name, 8)).".php");
			}

			break;
	}
}

?>