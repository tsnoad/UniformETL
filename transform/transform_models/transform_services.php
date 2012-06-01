<?php

class TransformServices {
	function comparable_plural($primary, $columns, $rows) {
		if (empty($rows)) return null;

		$comparable = array();

		foreach ($rows as $row) {
			$primary_id = $row[$primary];

			$comparable_row = array();

			foreach ($columns as $column) {
				$comparable_row[$column] = trim($row[$column]);
			}

			$comparison_hash = md5(implode("", $comparable_row));

			$comparable[$primary_id][$comparison_hash] = $comparable_row;
		}

		return $comparable;
	}

// 	comparable_plural("member_id", array("member_id", "email"), array());

	get_src(array(
		"type" => "plural",
		"tables" => array(
			array("table" => "GradeHistory"),
			array("table" => "cpgGradeType", "join" => "inner", "on" => array(
				"equals" => array(
					array("table" => "GradeHistory", "column" => "gradetypeid"),
					array("table" => "cpgGradeType", "column" => "gradetypeid"),
				)
			)),
		),
		"columns" => array(
			"member_id" => array("table" => "GradeHistory", "column" => "customerid"),
			"college" => array("table" => "GradeHistory", "column" => "gradetypeid"),
			"grade" => array("table" => "GradeHistory", "column" => "gradeid"),
		),
		"where" => array(
			array("table" => "GradeHistory", "column" => "cpgid", "equals" => "IEA"),
			array("table" => "cpgGradeType", "column" => "classid", "equals" => "COLL"),
			array("table" => "GradeHistory", "column" => "datechange", "equals" => ""),
			array("table" => "GradeHistory", "column" => "changereasonid", "equals" => ""),
		),
	));

	function get_src($spec) {
		foreach ($spec['columns'] as $column => $column_spec) {
			$select[] = "dump_%{extract_id}_".strtolower($column_spec['table']).".{$column_spec['$column']} AS {$column}";
		}
		foreach ($spec['tables'] as $table_spec) {
			if (isset($table_spec['join'])) {
				$from[] = "{$table_spec['join']} JOIN dump_%{extract_id}_".strtolower($table_spec['table'])." ON ()";
			} else {
				$from[] = "dump_%{extract_id}_".strtolower($table_spec['table']);
			}
		}
		echo "SELECT ".implode(", ", $select)." FROM";
	}
}

?>