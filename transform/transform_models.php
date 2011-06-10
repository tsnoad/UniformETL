<?php

require_once("/etc/uniformetl/autoload.php");

class Models {
	public $required_transforms;
	public $required_tables;
	public $transform_priority;
	
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
		//ask each transform what other transforms it depends upon
		$this->required_transforms = Plugins::hook("models_required-transforms", array());

		$this->required_tables = Plugins::hook("models_required-tables", array());

		$this->transform_priority = Plugins::hook("models_transform-priority", array());

		$this->calculate_requirements();
		$this->get_all_tables();
		$this->get_all_source_tables();
	}

	function calculate_requirements() {
		//loop through all the enabled transforms
		foreach (Conf::$do_transforms as $transform) {
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

		return $transform_class;
	}
}

?>