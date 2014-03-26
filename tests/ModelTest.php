<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

//require_once __DIR__ . '/../lib/model/model.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/MockDatabase.php';

class Users extends \Snowfire\Database\Model
{
	protected static $_required_parameters = array('account_id');
	
	/*public function by_id($id)
	{
		return $this->_query_select()->where('id', $id)->limit(1)->execute();
	}
	
	public function create($name)
	{
		return $this->_query_insert()->set('name', $name)->execute();
	}
	
	public function delete($id)
	{
		return $this->_query_delete()->where('id', $id)->execute();
	}
	
	public function change_name($id, $name)
	{
		return $this->_query_update()->set('name', $name)->where('id', $id)->execute();
	}*/
}

class ModelTest extends PHPUnit_Framework_TestCase
{
	private $_mock;
	
	private function _users_model($expected)
	{
		$this->_mock = new Mock_Database($this, $expected);
		\Snowfire\Database\Model::database($this->_mock);
		return new Users(array('account_id' => 5));
	}/*
	
	private function _base_model($expected)
	{
		$this->_mock = new Mock_Database($this, $expected);
		return new Base($this->_mock);
	}
	
	public function testBaseMany()
	{
		$base = $this->_base_model(array(
    		array('many', "SELECT `column`\nFROM base\nWHERE `column` = ?\nORDER BY `column`\nLIMIT 2", array('value'), null)
    	));
    	
    	$base->many(array(
    		'columns' => 'column',
    		'conditions' => array('column' => 'value'),
    		'limit' => 2,
    		'order_by' => 'column'
    	));
	}*/
	
    public function testSelect()
    {
    	$users = $this->_users_model(array(
    		array('one', "SELECT users.*\nFROM users\nWHERE users.account_id = ? AND `id` = ?\nLIMIT 1", array(5, 9), null)
    	));
    	
    	$users->one(9);
	}
	
    public function testInsert()
    {
    	$users = $this->_users_model(array(
    		array('execute', "INSERT INTO users\nSET users.account_id = ?, `name` = ?", array(5, 'Name'), null, array('inserted_id' => 99))
    	));
    	
    	$this->assertEquals(99, $users->create(array('name' => 'Name')));
	}
	
    public function testDelete()
    {
    	$users = $this->_users_model(array(
    		array('many', "SELECT *\nFROM users\nWHERE users.account_id = ? AND `id` = ?", array(5, 9), null, array('return' => array(
    			array('id' => '5'),
    			array('id' => '9')
    		))),
    		array('execute', "DELETE FROM users\nWHERE users.account_id = ? AND `id` = ?", array(5, 9))
    	));
    	
    	$users->delete(9);
	}
	
    public function testUpdate()
    {
    	$users = $this->_users_model(array(
    		array('execute', "UPDATE users\nSET `name` = ?\nWHERE users.account_id = ? AND `id` = ?", array('Name 2', 5, 9))
    	));
    	
    	$users->edit(9, array('name' => 'Name 2'));
	}
	
	public function tearDown()
	{
		$this->_mock->finished();
	}
}
