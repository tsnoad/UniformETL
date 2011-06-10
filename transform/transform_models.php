<?php

require_once("/etc/uniformetl/plugins.php");
require_once("/etc/uniformetl/autoload.php");

class Models {
	public $conf;

	public $required_transforms;

	public $required_tables = array(
		"MemberAddresses" => array("dump_address"),
		"MemberConfluenceStatuses" => array(),
		"MemberDivisions" => array("dump_cpgcustomer"),
		"MemberEcpdStatuses" => array("dump_groupmember"),
		"MemberEmails" => array("dump_email"),
		"MemberGrades" => array("dump_cpgcustomer"),
		"MemberInvoices" => array("dump_invoice"),
		"MemberIds" => array("dump_customer"),
		"MemberNames" => array("dump_name"),
		"MemberPasswords" => array(),
		"MemberPersonals" => array("dump_customer"),
		"MemberReceipts" => array("dump_receipt"),
		"MemberWebStatuses" => array("dump_cpgcustomer")
	);
	
	public $transform_priority = array(
		"MemberAddresses" => "secondary",
		"MemberConfluenceStatuses" => "tertiary",
		"MemberDivisions" => "secondary",
		"MemberEcpdStatuses" => "secondary",
		"MemberEmails" => "secondary",
		"MemberGrades" => "secondary",
		"MemberInvoices" => "secondary",
		"MemberIds" => "primary",
		"MemberNames" => "secondary",
		"MemberPasswords" => "secondary",
		"MemberPersonals" => "secondary",
		"MemberReceipts" => "secondary",
		"MemberWebStatuses" => "secondary"
	);
	
	public $dump_table_sources = array(
		"dump_address" => "Address",
		"dump_cpgcustomer" => "cpgCustomer",
		"dump_customer" => "Customer",
		"dump_email" => "EMail",
		"dump_groupmember" => "GroupMember",
		"dump_invoice" => "Invoice",
		"dump_name" => "Name",
		"dump_receipt" => "Receipt"
	);

	public $transforms;
	public $requirements;
	public $tables;
	public $sources;

	function start() {
		$this->gather_transform_requirements();

		$this->calculate_requirements();
		$this->get_all_tables();
		$this->get_all_source_tables();
	}

	function gather_transform_requirements() {
		//ask each transform what other transforms it depends upon
		$required_transforms_query = Plugins::hook("models_required-transforms", array());
		
		//loop through the response from each transform
		foreach ($required_transforms_query as $required_transforms_query_tmp) {
			//each transform should return an array that looks like this:
			//array("transform_name" => array("first_requrement", "second_requirement"))
			//check to make sure that we haven't recieved "transform_name" before
			if (in_array(reset(array_keys($required_transforms_query_tmp)), (array)$this->required_transforms)) {
				die("OMG");
			}

			//squash all the responses together
			$this->required_transforms = array_merge((array)$this->required_transforms, (array)$required_transforms_query_tmp);
		}
	}

	function calculate_requirements() {
		//loop through all the enabled transforms
		foreach ($this->conf->do_transforms as $transform) {
			//add it as a required transform
			$this->add_requirement($transform);
		
			//add any other transforms that it requires
			$this->get_subrequirements($transform);
		}

		//now we've got an array of transforms sorted by priority
		//loop through each priority...
		foreach ($this->requirements as $priority => $requirements_priorities) {
			//and weed out any duplicates
			$this->requirements[$priority] = array_unique($requirements_priorities);
		}

		$this->transforms = array_merge((array)$this->requirements['primary'], (array)$this->requirements['secondary'], (array)$this->requirements['tertiary']);
	}

	function add_requirement($transform) {
		//what's the priority of this transform
		$priority = $this->transform_priority[$transform];

		//put it in the right sub-array
		$this->requirements[$priority][] = $transform;
	}

	function get_subrequirements($transform) {
		//loop though all the required child transforms for this parent
		foreach ($this->required_transforms[$transform] as $required_transform) {
			//what's the priority of the parent transform
			$transform_priority = $this->transform_priority[$transform];
			//what's the priority of the child transform
			$subtransform_priority = $this->transform_priority[$required_transform];

			//assign numbers to priority names: it'll make comparisons easier
			$priorities = array("primary" => 3, "secondary" => 2, "tertiary" => 1);

			//parent priority
			$transform_priority = $priorities[$transform_priority];
			//child priority
			$subtransform_priority = $priorities[$subtransform_priority];

			//child's priority can't be higher) than (or the same as parent's priority
			if ($subtransform_priority <= $transform_priority) {
				die("priorities are messed up");
			}

			//add the child as a required transform
			$this->add_requirement($required_transform);

			//as
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
/* 		var_dump(class_exists($transform)); */

		$transform_class = New $transform;

		$transform_class->conf = $this->conf;

		return $transform_class;
	}
}

?>