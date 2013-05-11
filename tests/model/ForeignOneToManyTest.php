<?php

/**
* Copyright 2012, Snowfire AB, snowfireit.com
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

require_once '../library/database/model/model.php';

class Products extends Lib\Database_Model
{
	protected static $_singular = 'product';
	protected static $_foreign = array(
		array('type' => 'one_to_many', 'name' => 'options')
	);
}

class Products_Options extends Lib\Database_Model
{
	protected static $_plural = 'options';
}

class ForeignOneToManyTest extends PHPUnit_Framework_TestCase
{
	private $_mock;
	
	public function testBase()
	{
		$this->assertEquals('products', Products::table());
		$this->assertEquals('products_options', Products_Options::table());
		$this->assertEquals('products', Products::plural());
		$this->assertEquals('options', Products_Options::plural());
	}
	
	public function testMany()
	{
		$this->_mock = new Mock_Database($this, array(
			array('many', "SELECT products.*\nFROM products", array(), null, array('return' => array(
				array('id' => 1),
				array('id' => 2)
			))),
			array('many', "SELECT products_options.*\nFROM products_options\nWHERE `product_id` IN (?, ?)", array(1, 2), null, array('return' => array(
				array('id' => 3, 'product_id' => 1),
				array('id' => 4, 'product_id' => 2)
			)))
		));
		
		$prod_model = new Products($this->_mock);
		$opt_model = new Products_Options($this->_mock);
		
		$this->assertEquals(
			array(
				array('id' => 1, 'options' => array(array('id' => 3, 'product_id' => 1))),
				array('id' => 2, 'options' => array(array('id' => 4, 'product_id' => 2)))
			), 
			$prod_model->many(array(
				'foreign_models' => array('options' => $opt_model)
			))
		);
	}
	
	public function tearDown()
	{
		if (isset($this->_mock)) {
			$this->_mock->finished();
		}
		
		unset($this->_mock);
	}
}
