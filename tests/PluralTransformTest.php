<?php

require_once("/etc/uniformetl/transform/pluraltransforms.php");

class PluralTransformsTest extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->transform = new PluralTransforms;

		$this->data = array(
			"10000000" => array(
				md5("10000000"."content 1"."content 2") => array(
					"member_id" => "10000000",
					"field1" => "content 1",
					"field2" => "content 2"
				)
			)
		);
	}

	protected function tearDown() {
	}

	public function testAdd() {
		$src_data = $this->data;
		$dst_data = null;

		$return = $this->transform->transform($src_data, $dst_data);
		$this->assertNotEmpty($return);

		list($data_add, $data_nochange, $data_update, $data_delete, $data_delete_count) = $return;

		$this->assertNotEmpty($data_add);
		$this->assertNotEmpty($data_add[0]);
		$this->assertEquals($src_data['10000000'][md5("10000000"."content 1"."content 2")], $data_add[0]);

		$this->assertEmpty($data_nochange);
		$this->assertEmpty($data_update);
		$this->assertEmpty($data_delete);
		$this->assertEmpty($data_delete_count);
	}

	public function testNoChange() {
		$src_data = $this->data;
		$dst_data = $this->data;

		$return = $this->transform->transform($src_data, $dst_data);
		$this->assertNotEmpty($return);

		list($data_add, $data_nochange, $data_update, $data_delete, $data_delete_count) = $return;

		$this->assertEmpty($data_add);
		$this->assertNotEmpty($data_nochange);
		$this->assertNotEmpty($data_nochange[0]);
		$this->assertEquals($src_data['10000000'][md5("10000000"."content 1"."content 2")], $data_nochange[0]);

		$this->assertEmpty($data_update);
		$this->assertEmpty($data_delete);
		$this->assertEmpty($data_delete_count);
	}

	public function testUpdate() {
		$updated_data = $this->data['10000000'][md5("10000000"."content 1"."content 2")];
		$updated_data['field1'] = "arglefragle";
		$updated_data_hash = md5(implode("", $updated_data));

		$src_data['10000000'][$updated_data_hash] = $updated_data;
		$dst_data = $this->data;

		$return = $this->transform->transform($src_data, $dst_data);
		$this->assertNotEmpty($return);

		list($data_add, $data_nochange, $data_update, $data_delete, $data_delete_count) = $return;

		$this->assertNotEmpty($data_add);
		$this->assertNotEmpty($data_add[0]);
		$this->assertEquals($src_data['10000000'][$updated_data_hash], $data_add[0]);

		$this->assertEmpty($data_nochange);
		$this->assertEmpty($data_update);

		$this->assertNotEmpty($data_delete);
		$this->assertNotEmpty($data_delete['10000000']);
		$this->assertNotEmpty($data_delete['10000000'][md5("10000000"."content 1"."content 2")]);
		$this->assertEquals($dst_data['10000000'][md5("10000000"."content 1"."content 2")], $data_delete['10000000'][md5("10000000"."content 1"."content 2")]);

		$this->assertNotEmpty($data_delete_count);
		$this->assertEquals(count($dst_data['10000000']), $data_delete_count);
	}

	public function testDelete() {
		$src_data = null;
		$dst_data = $this->data;

		$return = $this->transform->transform($src_data, $dst_data);
		$this->assertNotEmpty($return);

		list($data_add, $data_nochange, $data_update, $data_delete, $data_delete_count) = $return;

		$this->assertEmpty($data_add);
		$this->assertEmpty($data_nochange);
		$this->assertEmpty($data_update);

		$this->assertNotEmpty($data_delete);
		$this->assertNotEmpty($data_delete['10000000'][md5("10000000"."content 1"."content 2")]);
		$this->assertEquals($dst_data['10000000'][md5("10000000"."content 1"."content 2")], $data_delete['10000000'][md5("10000000"."content 1"."content 2")]);

		$this->assertNotEmpty($data_delete_count);
		$this->assertEquals(count($dst_data['10000000']), $data_delete_count);
	}
}

?>