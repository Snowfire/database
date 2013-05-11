<?php

/**
* Copyright 2012, Snowfire AB, snowfireit.com
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

class DatabaseTest extends PHPUnit_Framework_TestCase
{
	/**
	* @var \Lib\Database
	*/
	private $_db;
	
	public function setUp()
	{
		global $database;
		$this->_db = $database;
		
		$this->_db->execute("DROP TABLE IF EXISTS `test`");
		$this->_db->execute("CREATE TABLE `test`( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `name` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`) )");
		$this->_db->execute("INSERT INTO test SET name = 'Test 1'");
		$this->_db->execute("INSERT INTO test SET name = 'Test 2'");
	}
	
	public function tearDown()
	{
		$this->_db->execute("DROP TABLE IF EXISTS `test`");
	}
	
    public function testLastInsertId()
    {
    	$this->_db->execute('INSERT INTO test SET name = ?', array('Test 3'));
    	$this->assertEquals(3, $this->_db->last_insert_id());
    	
    	$this->_db->execute('INSERT INTO test SET name = ?', array('Test 4'));
    	$this->assertEquals(4, $this->_db->last_insert_id());
	}
	
	public function testOne()
	{
		$this->assertEquals(
			array('id' => '2', 'name' => 'Test 2'),
			$this->_db->one('SELECT * FROM test WHERE id = ?', array(2))
		);
		
		$this->assertEquals(
			'Test 2',
			$this->_db->one('SELECT name FROM test WHERE id = ?', array(2), array('single_column' => true))
		);
	}
	
	public function testMany()
	{
		$this->assertEquals(
			array(
				array('id' => '1', 'name' => 'Test 1'),
				array('id' => '2', 'name' => 'Test 2')
			),
			$this->_db->many('SELECT * FROM test WHERE id IN (?, ?)', array(1, 2))
		);
		
		$this->assertEquals(
			array('Test 1', 'Test 2'),
			$this->_db->many('SELECT name FROM test WHERE id IN (?, ?)', array(1, 2), array('single_column' => true))
		);
	}
}
