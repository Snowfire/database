<?php

/**
* Copyright 2012, Snowfire AB, snowfireit.com
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

require_once '../library/database/model/model.php';

class People extends Lib\Database_Model
{
	protected static $_foreign = array(
		array('type' => 'one_to_one', 'name' => 'passports')
	);
}

class Passports extends Lib\Database_Model
{
	protected static $_singular = 'passport';
}

class ForeignOneToOneTest extends PHPUnit_Framework_TestCase
{
	private $_mock;
	
	public function testBase()
	{
		$this->assertEquals('passports', Passports::table());
		$this->assertEquals('people', People::table());
		$this->assertEquals('passports', Passports::plural());
		$this->assertEquals('people', People::plural());
	}
	
	public function testMany()
	{
		$this->_mock = new Mock_Database($this, array(
			array('many', "SELECT people.*\nFROM people", array(), null, array('return' => array(
				array('id' => 1, 'passport_id' => 3),
				array('id' => 2, 'passport_id' => 4)
			))),
			array('many', "SELECT passports.*\nFROM passports\nWHERE `id` IN (?, ?)", array(3, 4), null, array('return' => array(
				array('id' => 3),
				array('id' => 4)
			)))
		));
		
		$people_model = new People($this->_mock);
		$passports_model = new Passports($this->_mock);
		
		$this->assertEquals(
			array(
				array('id' => 1, 'passport' => array('id' => 3)),
				array('id' => 2, 'passport' => array('id' => 4))
			),
			$people_model->many(array(
				'foreign_models' => array('passports' => $passports_model)
			))
		);
	}
	
	public function testCreate()
	{
		$this->_mock = new Mock_Database($this, array(
			array('execute', "INSERT INTO passports\nSET `col` = ?", array('val'), null, array('inserted_id' => 1)),
			array('execute', "INSERT INTO people\nSET `name` = ?, `passport_id` = ?", array('Name', 1), null, array('inserted_id' => 2))
		));
		
		$people_model = new People($this->_mock);
		$passports_model = new Passports($this->_mock);
		
		$this->assertEquals(2,
			$people_model->create(array(
				'name' => 'Name',
				'passport' => array(
					'col' => 'val'
				)
			), array('passports' => $passports_model))
		);
	}
	
	public function testDelete()
	{
		$this->_mock = new Mock_Database($this, array(
			array('many', "SELECT *\nFROM people\nWHERE `id` IN (?, ?)", array(1, 2), null, array('return' => array(
				array('id' => '1', 'passport_id' => 3),
				array('id' => '2', 'passport_id' => 4)
			))),
			array('execute', "DELETE FROM people\nWHERE `id` IN (?, ?)", array(1, 2)),
			array('many', "SELECT *\nFROM passports\nWHERE `id` IN (?, ?)", array(3, 4), null, array('return' => array(
				array('id' => '3'),
				array('id' => '4')
			))),
			array('execute', "DELETE FROM passports\nWHERE `id` IN (?, ?)", array(3, 4))
		), array('debug' => false));
		
		$people_model = new People($this->_mock);
		$passports_model = new Passports($this->_mock);

		$people_model->delete(array('id' => array(1, 2)), array('passports' => $passports_model));
	}
	
	public function tearDown()
	{
		if (isset($this->_mock)) {
			$this->_mock->finished();
		}
		
		unset($this->_mock);
	}
}
