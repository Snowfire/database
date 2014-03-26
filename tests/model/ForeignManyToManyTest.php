<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

//require_once __DIR__ . '/../../lib/model/model.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../MockDatabase.php';

class Products1 extends \Snowfire\Database\Model
{
	protected static $_table = 'products';
	protected static $_singular = 'product';
	protected static $_foreign = array(
		array('type' => 'many_to_many', 'name' => 'categories')
	);
}

class Categories1 extends \Snowfire\Database\Model
{
	protected static $_table = 'categories';
	protected static $_singular = 'category';
}

class ForeignManyToManyTest extends PHPUnit_Framework_TestCase
{
	private $_mock;
	
	public function testCreate()
	{
		$this->_mock = new Mock_Database($this, array(
			array('execute', "INSERT INTO products\nSET `name` = ?", array('Name'), null, array('inserted_id' => 3)),
			array('execute', "INSERT INTO products_categories\nSET `product_id` = ?, `category_id` = ?", array(3, 1)),
			array('execute', "INSERT INTO products_categories\nSET `product_id` = ?, `category_id` = ?", array(3, 2))
		), array('debug' => false));

		\Snowfire\Database\Model::database($this->_mock);
		
		$prod_model = new Products1();
		$cat_model = new Categories1();
		
		$this->assertEquals(
			3, 
			$prod_model->create(array(
				'name' => 'Name',
				'category_ids' => array(1, 2)
			), array('categories' => $cat_model))
		);
	}
	
	public function testMany()
	{
		$this->_mock = new Mock_Database($this, array(
			array('many', "SELECT products.*, GROUP_CONCAT(DISTINCT products_categories.category_id) AS category_ids\n" .
				"FROM products\nLEFT JOIN `products_categories`\n\tON products_categories.product_id = id\nGROUP BY `id`",
				array(), null, array('return' => array(
					array('id' => '1', 'category_ids' => '3,4'),
					array('id' => '2', 'category_ids' => null)
				))),
			array('many', "SELECT categories.*\nFROM categories\nWHERE `id` IN (?, ?)", array(3, 4), null, array('return' => array(
				array('id' => '3'),
				array('id' => '4')
			)))
		), array('debug' => false));

		\Snowfire\Database\Model::database($this->_mock);
		
		$prod_model = new Products1();
		$cat_model = new Categories1();
		
		$this->assertEquals(
			array(
				array('id' => 1, 'categories' => array(array('id' => 3), array('id' => 4))),
				array('id' => 2, 'categories' => array())
			), 
			$prod_model->many(array(
				'foreign_models' => array('categories' => $cat_model)
			))
		);
	}
	
	public function testDelete()
	{
		$this->_mock = new Mock_Database($this, array(
			array('many', "SELECT *\nFROM products\nWHERE `id` = ?", array(1), null, array('return' => array(array('id' => '1')))),
			array('execute', "DELETE FROM products_categories\nWHERE `product_id` IN (?)", array(1)),
			array('execute', "DELETE FROM products\nWHERE `id` = ?", array(1))
		), array('debug' => false));

		\Snowfire\Database\Model::database($this->_mock);
		
		$prod_model = new Products1();
		$cat_model = new Categories1();
		
		$prod_model->delete(array('id' => 1), array('categories' => $cat_model));
	}
	
	public function tearDown()
	{
		if (isset($this->_mock)) {
			$this->_mock->finished();
		}
		
		unset($this->_mock);
	}
}
