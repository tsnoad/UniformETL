<?php

function __autoload($class_name) {
	switch ($class_name) {
		case "Plugins":
		    require_once("/etc/uniformetl/".strtolower($class_name).".php");
			break;

		case "SingleTransforms":
		case "PluralTransforms":
		case "Chunks":
		case "GlobalTiming":
		    require_once("/etc/uniformetl/transform/".strtolower($class_name).".php");
			break;

		case "MemberIds":
		case "MemberPersonals":
		case "MemberPasswords":
		case "MemberNames":
		case "MemberEmails":
		case "MemberAddresses":
		case "MemberInvoices":
		case "MemberReceipts":
		case "MemberGrades":
		case "MemberDivisions":
		    require_once("/etc/uniformetl/transform/transform_models/member_".strtolower(substr($class_name, 6)).".php");
			break;
		case "MemberWebStatuses":
		    require_once("/etc/uniformetl/transform/transform_models/member_web_statuses.php");
			break;
		case "MemberEcpdStatuses":
		    require_once("/etc/uniformetl/transform/transform_models/member_ecpd_statuses.php");
			break;
		case "MemberConfluenceStatuses":
		    require_once("/etc/uniformetl/transform/transform_models/member_confluence_statuses.php");
			break;

		default:
			if (strpos($class_name, "APIModel") === 0) {
			    require_once("/etc/uniformetl/api/apimodel_".strtolower(substr($class_name, 8)).".php");
			}

			break;
	}
}

?>