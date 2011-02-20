<?php

require_once("transform_models/member_ids.php");
require_once("transform_models/member_passwords.php");
require_once("transform_models/member_names.php");
require_once("transform_models/member_emails.php");
require_once("transform_models/member_addresses.php");
require_once("transform_models/member_web_statuses.php");
require_once("transform_models/member_ecpd_statuses.php");
require_once("transform_models/member_confluence_statuses.php");
require_once("transform_models/member_invoices.php");
require_once("transform_models/member_receipts.php");

class Models {
	public $required_transforms = array(
		"addresses" => array("member_ids"),
		"confluence_statuses" => array("member_ids", "names", "emails", "passwords", "web_statuses"),
		"ecpd_statuses" => array("member_ids"),
		"emails" => array("member_ids"),
		"invoices" => array("member_ids"),
		"member_ids" => array(),
		"names" => array("member_ids"),
		"passwords" => array("member_ids"),
		"receipts" => array("member_ids"),
		"web_statuses" => array("member_ids")
	);

	public $required_tables = array(
		"addresses" => array("dump_address"),
		"confluence_statuses" => array(),
		"ecpd_statuses" => array("dump_groupmember"),
		"emails" => array("dump_email"),
		"invoices" => array("dump_invoice"),
		"member_ids" => array("dump_cpgcustomer"),
		"names" => array("dump_name"),
		"passwords" => array(),
		"receipts" => array("dump_receipt"),
		"web_statuses" => array("dump_cpgcustomer")
	);
	
	public $transform_priority = array(
		"addresses" => "secondary",
		"confluence_statuses" => "tertiary",
		"ecpd_statuses" => "secondary",
		"emails" => "secondary",
		"invoices" => "secondary",
		"member_ids" => "primary",
		"names" => "secondary",
		"passwords" => "secondary",
		"receipts" => "secondary",
		"web_statuses" => "secondary"
	);
	
	//indexes are written as required
/*
	public $indexes = array(
		"addresses" => array("CREATE INDEX dump_address_customerid ON dump_address (cast(customerid AS BIGINT));"),
		"confluence_statuses" => array(),
		"ecpd_statuses" => array(
			"CREATE INDEX dump_groupmember_groupid ON dump_groupmember (groupid) WHERE (groupid='6052');",
			"CREATE INDEX dump_groupmember_customerid ON dump_groupmember (cast(customerid AS BIGINT)) WHERE (groupid='6052');"
		),
		"emails" => array(
			"CREATE INDEX dump_email_emailtypeid ON dump_email (emailtypeid) WHERE (emailtypeid='INET');",
			"CREATE INDEX dump_email_customerid ON dump_email (cast(customerid AS BIGINT)) WHERE (emailtypeid='INET');"
		),
		"member_ids" => array(
			"CREATE INDEX dump_cpgcustomer_cpgid ON dump_cpgcustomer (cpgid) WHERE (cpgid='IEA');",
			"CREATE INDEX dump_cpgcustomer_customerid ON dump_cpgcustomer (cast(customerid AS BIGINT)) WHERE (cpgid='IEA');",
			"CREATE INDEX dump_cpgcustomer_custstatusid ON dump_cpgcustomer (custstatusid) WHERE (custstatusid='MEMB');"
		),
		"names" => array("CREATE INDEX dump_name_customerid ON dump_name (cast(customerid AS BIGINT));"),
		"passwords" => array(),
		"web_statuses" => array()
	);
*/
	
	public $dump_table_sources = array(
		"dump_address" => "Address",
		"dump_cpgcustomer" => "cpgCustomer",
		"dump_email" => "EMail",
		"dump_invoice" => "Invoice",
		"dump_groupmember" => "GroupMember",
		"dump_name" => "Name",
		"dump_receipt" => "Receipt"
	);

	public $transforms;
	public $requirements;
	public $tables;
	public $sources;

	function start() {
		$this->calculate_requirements();
		$this->get_all_tables();
		$this->get_all_source_tables();

/*
		print_r($this->transforms);
		print_r($this->tables);
		print_r($this->sources);
*/
	}

	function calculate_requirements() {
		foreach ($this->conf->do_transforms as $transform) {
			$this->add_requirement($transform);
		
			$this->get_subrequirements($transform);
		}

		foreach ($this->requirements as $priority => $requirements_priorities) {
			$this->requirements[$priority] = array_unique($requirements_priorities);
		}

		$this->transforms = array_merge((array)$this->requirements['primary'], (array)$this->requirements['secondary'], (array)$this->requirements['tertiary']);
	}

	function add_requirement($transform) {
		$priority = $this->transform_priority[$transform];

		$this->requirements[$priority][] = $transform;
	}

	function get_subrequirements($transform) {
		foreach ($this->required_transforms[$transform] as $required_transform) {
			$transform_priority = $this->transform_priority[$transform];
			$subtransform_priority = $this->transform_priority[$required_transform];

			$priorities = array("primary" => 3, "secondary" => 2, "tertiary" => 1);

			$transform_priority = $priorities[$transform_priority];
			$subtransform_priority = $priorities[$subtransform_priority];

			if ($subtransform_priority <= $transform_priority) {
				die("priorities are messed up");
			}

			$this->add_requirement($required_transform);

			if ($this->transform_priority[$required_transform] != "primary") {
				$this->get_subrequirements($required_transform);
			}
		}
	}

	function get_all_tables() {
		foreach ($this->transforms as $transform) {
			foreach ($this->required_tables[$transform] as $required_table) {
				$this->tables[] = $required_table;
			}
		}
		
		$this->tables = array_unique($this->tables);
	}

	function get_all_source_tables() {
		foreach ($this->tables as $table) {
			$this->sources[] = $this->dump_table_sources[$table];
		}
	}

	function init_class($transform) {
		switch ($transform) {
			case "member_ids":
				$transform_class = New MemberIds;
				break;
			case "passwords":
				$transform_class = New MemberPasswords;
				break;
			case "names":
				$transform_class = New MemberNames;
				break;
			case "emails":
				$transform_class = New MemberEmails;
				break;
			case "addresses":
				$transform_class = New MemberAddresses;
				break;
			case "web_statuses":
				$transform_class = New MemberWebStatuses;
				break;
			case "ecpd_statuses":
				$transform_class = New MemberEcpdStatuses;
				break;
			case "confluence_statuses":
				$transform_class = New MemberConfluenceStatuses;
				break;
			case "invoices":
				$transform_class = New MemberInvoices;
				break;
			case "receipts":
				$transform_class = New MemberReceipts;
				break;
		}

		return $transform_class;
	}
}

?>