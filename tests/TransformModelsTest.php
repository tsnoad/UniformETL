<?php

require_once("/etc/uniformetl/transform/transform_models.php");

class TransformModelsTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->models = new Models;
	}

	protected function tearDown() {
	}

	public function testCheckRequiredSucceed() {
		$models = array("one", "two");
		$requirements = array("one" => array("two"));

		$this->models->check_required_models($models, $requirements);
	}

	public function testCheckRequiredFail() {
		$models = array("one", "two");
		$requirements = array("one" => array("two"), "two" => array("three"));
		
		try {
            $this->models->check_required_models($models, $requirements);
        } catch (Exception $expected) {
            return;
        }
 
        $this->fail('An expected exception has not been raised.');
	}

	public function testDefineTablesSucceed() {
		$models = array("one", "two");
		$requirements = array("one" => array("dump_%{extract_id}_table1" => "table1source", "dump_%{extract_id}_table1more" => "table1moresource"), "two" => array("dump_%{extract_id}_table2" => "table2source"));

		list($extract_tables, $source_tables) = $this->models->define_tables($models, $requirements);

		$this->assertEquals(array("dump_%{extract_id}_table1", "dump_%{extract_id}_table1more", "dump_%{extract_id}_table2"), $extract_tables);
		$this->assertEquals(array("table1source", "table1moresource", "table2source"), $source_tables);
	}

	public function testDefineTablesExtractEmpty() {
		$models = array("one");
		$requirements = array("one" => array(""));

		try {
			list($extract_tables, $source_tables) = $this->models->define_tables($models, $requirements);
        } catch (Exception $expected) {
            return;
        }
 
        $this->fail('An expected exception has not been raised.');
	}

	public function testDefineTablesExtractNotString() {
		$models = array("one");
		$requirements = array("one" => array(1234));

		try {
			list($extract_tables, $source_tables) = $this->models->define_tables($models, $requirements);
        } catch (Exception $expected) {
            return;
        }
 
        $this->fail('An expected exception has not been raised.');
	}

	public function testDefineTablesSourceEmpty() {
		$models = array("one");
		$requirements = array("one" => array("dump_%{extract_id}_table1" => ""));

		try {
			list($extract_tables, $source_tables) = $this->models->define_tables($models, $requirements);
        } catch (Exception $expected) {
            return;
        }
 
        $this->fail('An expected exception has not been raised.');
	}

	public function testDefineTablesSourceNotString() {
		$models = array("one");
		$requirements = array("one" => array("dump_%{extract_id}_table1" => 1234));

		try {
			list($extract_tables, $source_tables) = $this->models->define_tables($models, $requirements);
        } catch (Exception $expected) {
            return;
        }
 
        $this->fail('An expected exception has not been raised.');
	}

	public function testDefineTablesNoExtractId() {
		$models = array("one");
		$requirements = array("one" => array("dump_squiggles_table1" => "table1source"));

		try {
			list($extract_tables, $source_tables) = $this->models->define_tables($models, $requirements);
        } catch (Exception $expected) {
            return;
        }
 
        $this->fail('An expected exception has not been raised.');
	}

	public function testDefineModelsSucceed() {
		$models = array("one", "two", "three");
		$model_priorities = array("one" => "tertiary", "two" => "primary", "three" => "secondary");

		$models = $this->models->define_models($models, $model_priorities);

		$this->assertEquals(array("two", "three", "one"), $models);
	}

	public function testDefineModelsNoPriority() {
		$models = array("one");
		$model_priorities = array("one" => "");

		try {
			list($extract_tables, $source_tables) = $this->models->define_models($models, $model_priorities);
        } catch (Exception $expected) {
            return;
        }
 
        $this->fail('An expected exception has not been raised.');
	}

	public function testDefineModelsBadPriority() {
		$models = array("one");
		$model_priorities = array("one" => "squiggles");

		try {
			list($extract_tables, $source_tables) = $this->models->define_models($models, $model_priorities);
        } catch (Exception $expected) {
            return;
        }
 
        $this->fail('An expected exception has not been raised.');
	}
}

?>