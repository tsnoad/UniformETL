<?php

require_once("/etc/uniformetl/plugins.php");
require_once("/etc/uniformetl/autoload.php");

class Models {
	public $conf;

	public $required_transforms = array(
/*
		"addresses" => array("member_ids"),
		"confluence_statuses" => array("member_ids", "names", "emails", "passwords", "web_statuses"),
		"ecpd_statuses" => array("member_ids"),
		"emails" => array("member_ids"),
		"invoices" => array("member_ids"),
		"member_ids" => array(),
		"names" => array("member_ids"),
		"passwords" => array("member_ids"),
		"personals" => array("member_ids"),
		"receipts" => array("member_ids"),
		"web_statuses" => array("member_ids"),
		"grades" => array("member_ids"),
		"divisions" => array("member_ids")
*/
	);

	public $required_tables = array(
		"addresses" => array("dump_address"),
		"confluence_statuses" => array(),
		"ecpd_statuses" => array("dump_groupmember"),
		"emails" => array("dump_email"),
		"invoices" => array("dump_invoice"),
		"member_ids" => array("dump_customer"),
		"names" => array("dump_name"),
		"passwords" => array(),
		"personals" => array("dump_customer"),
		"receipts" => array("dump_receipt"),
		"web_statuses" => array("dump_cpgcustomer"),
		"grades" => array("dump_cpgcustomer"),
		"divisions" => array("dump_cpgcustomer")
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
		"personals" => "secondary",
		"receipts" => "secondary",
		"web_statuses" => "secondary",
		"grades" => "secondary",
		"divisions" => "secondary"
	);
	
	public $dump_table_sources = array(
		"dump_address" => "Address",
		"dump_cpgcustomer" => "cpgCustomer",
		"dump_customer" => "Customer",
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
	}

	function calculate_requirements() {
		$required_transforms_query = Plugins::hook("models_required-transforms", array());
		
		foreach ($required_transforms_query as $required_transforms_query_tmp) {
			if (in_array(reset(array_keys($required_transforms_query_tmp)), (array)$this->required_transforms)) {
				die("OMG");
			}

			$this->required_transforms = array_merge((array)$this->required_transforms, (array)$required_transforms_query_tmp);
		}

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
			case "personals":
				$transform_class = New MemberPersonals;
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
			case "grades":
				$transform_class = New MemberGrades;
				break;
			case "divisions":
				$transform_class = New MemberDivisions;
				break;
		}

		$transform_class->conf = $this->conf;

		return $transform_class;
	}
}

?>