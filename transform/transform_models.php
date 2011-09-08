<?php

/* php -r 'require("/etc/uniformetl/transform/transform_models.php"); $models = New Models; $models->start(); print_r($models->tables); print_r($models->sources);' */

require_once("/etc/uniformetl/autoload.php");

class Models {
	public $transforms;
	public $tables;
	public $sources;

	function start() {
		//ask each model what other models it depends upon
		$required_transforms = Plugins::hook("models_required-transforms", array());

		//make sure that every required model is enabled in the config file
		$this->check_required_models(Conf::$do_transforms, $required_transforms);

		//ask each model what tables it depends upon
		$required_tables = Plugins::hook("models_required-tables", array());

		//create arrays of source tables and extract tables that can be used by the extractors
		list($this->tables, $this->sources) = $this->define_tables(Conf::$do_transforms, $required_tables);

		$required_columns = Plugins::hook("models_required-columns", array());

		$this->columns = $this->define_columns(Conf::$do_transforms, $this->tables, $required_columns);

		//ask each model how important it is
		$transform_priority = Plugins::hook("models_transform-priority", array());

		//create an array of models, sorted by priority, so that more important models get run first
		$this->transforms = $this->define_models(Conf::$do_transforms, $transform_priority);
	}

	/*
	 * make sure that every required model is enabled in the config file
	 */
	function check_required_models($models, $requirements) {
		//loop through all the enabled models
		foreach ($models as $model) {
			//if this model has requirements
			if (!empty($requirements[$model])) {
				//loop though the models that are required
				foreach ($requirements[$model] as $required_model) {
					//if the required model isn't defined in config...
					if (!in_array($required_model, $models)) {
						//tell the user that they have to enable it
						throw new Exception("Model {$model} requires {$required_model}. It must be enabled in config.php");
					}
				}
			}
		}

		//if we got to here then all the required models are enabled
		return;
	}

	/*
	 * create arrays of source tables and extract tables that can be used by the extractors
	 */
	function define_tables($models, $requirements) {
		//loop through all the enabled models
		foreach ($models as $model) {
			//if this model has required tables
			if (!empty($requirements[$model])) {
				//loop though the tables that are required
				//each model will return an array that looks like this array("dump_cpgcustomer" => "cpgCustomer")
				//"cpgCustomer" is the source table that exists on the data source
				//"dump_cpgcustomer" is the extract table where we put data extracted from the source
				foreach ($requirements[$model] as $required_extract_table => $required_source_table) {
					//make sure the requried table (the key) is valid
					if (empty($required_extract_table) || !is_string($required_extract_table)) {
						throw new Exception("required extract table '{$required_extract_table}' does not appear to be valid for model {$model}.");
					}
					//make sure the requried table (the key) has the placeholder for the extract_id
					if (strpos($required_extract_table, "%{extract_id}") === FALSE) {
						throw new Exception("required extract table '{$required_extract_table}' does not have a valid extract id placeholder for model {$model}.");
					}
					//make sure the requried source table (the value) is valid
					if (empty($required_source_table) || !is_string($required_source_table)) {
						throw new Exception("required source table '{$required_source_table}' does not appear to be valid for model {$model}.");
					}

					//create an array of tables
					//there may be duplicates, but we'll take care of that later
					$extract_tables[] = $required_extract_table;
					$source_tables[] = $required_source_table;
				}
			}
		}

		//clean up any duplicate tables
		$extract_tables = array_unique($extract_tables);
		$source_tables = array_unique($source_tables);

		//return both arrays
		return array($extract_tables, $source_tables);
	}

	function define_columns($models, $tables, $requirements) {
		foreach ($models as $model) {
			if (!empty($requirements[$model])) {
				foreach ($requirements[$model] as $required_source_table => $required_source_columns) {
					$source_table_columns[$required_source_table] = array_merge((array)$source_table_columns[$required_source_table], (array)$required_source_columns);
				}
			}
		}

		foreach ($source_table_columns as $source_table => $source_columns) {
			$source_table_columns_uniq[$source_table] = array_unique($source_columns);
		}

		return $source_table_columns_uniq;
	}

	/*
	 * create an array of models, sorted by priority, so that more important models get run first
	 */
	function define_models($models, $model_priorities) {
		//loop through all the enabled models
		foreach ($models as $model) {
			//all models must have a priority that's either primary, secondary or tertiary
			//these priority names we can sort alphabetcally, which is handy
			if (empty($model_priorities[$model]) || !in_array($model_priorities[$model], array("primary", "secondary", "tertiary"))) {
				throw new Exception("Model {$model} does not have a correctly defined priority.");
			}

			//create an array of priorities.
			//the priorities in this array will line up models in $models
			$priorities[] = $model_priorities[$model];
		}

		//sort the models by priority
		$sort_success = array_multisort($priorities, $models);

		//array_multisort will fail if the arrays don't have the same number of elements
		if (!$sort_success) {
			throw new Exception("failed to sort models by priority.");
		}

		//return the sorted array of models
		return $models;
	}

	function init_class($transform) {
/* 		var_dump(class_exists($transform)); */

		$transform_class = New $transform;

		return $transform_class;
	}
}

?>