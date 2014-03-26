<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

//require_once __DIR__ . '/../../lib/query/conditions.php';
require_once __DIR__ . '/../../vendor/autoload.php';
//require_once __DIR__ . '/../MockDatabase.php';

class ConditionsTest extends PHPUnit_Framework_TestCase
{
    public function testShorthand()
    {
    	// id = 5
        // $c->add('id', 5);
    	$this->_test(
			'id', 5, 
			array(
				'sql' => '`id` = ?', 
				'values' => array(5)
			)
    	);
	}
	
	public function testKeyValue()
	{
        // id = 5 AND state = 'ACTIVE'
        // $c->add(array('id' => 5, 'state' => 'ACTIVE'));
        $this->_test(
			array('id' => 5, 'state' => 'ACTIVE'),
			array(
				'sql' => '`id` = ? AND `state` = ?', 
				'values' => array(5, 'ACTIVE')
			)
        );
		
	}
	
	public function testCustom()
	{
        // (publish_date IS NULL OR publish_date > NOW()) AND state = 'ACTIVE'
        // $c->add("(publish_date = ? OR publish_date > ?) AND state = ?", array(null, time(), 'ACTIVE'));
        $t = time();
		$this->_test(
			'(publish_date = ? OR publish_date > ?) AND state = ? AND updated_date != ? AND id != ?',
			array(null, $t, 'ACTIVE', null, array(1, 3, 5, 7, 9)),
			array(
				'sql' => '(publish_date IS NULL OR publish_date > ?) AND state = ? AND updated_date IS NOT NULL AND id NOT IN (?, ?, ?, ?, ?)',
				'values' => array($t, 'ACTIVE', 1, 3, 5, 7, 9)
			)
        );
    }
	
	public function testMultiple()
	{
		/*
		$c->add(array(
			array('id', 5),
			array(array('id' => 5, 'state' => 'ACTIVE')),
			array(
				'(publish_date = ? OR publish_date > ?) AND state = ? AND updated_date != ? AND id != ?',
				array(null, $t, 'ACTIVE', null, array(1, 3, 5, 7, 9))
			)
		))
        */
        
        $t = time();
		$this->_test(
			array(
				array('id', 5),
				array(array('id' => 5, 'state' => 'ACTIVE')),
				array(
					'(publish_date = ? OR publish_date > ?) AND state = ? AND updated_date != ? AND id != ?',
					array(null, $t, 'ACTIVE', null, array(1, 3, 5, 7, 9))
				)
			),
			array(
				'sql' => '`id` = ? AND `id` = ? AND `state` = ? AND (publish_date IS NULL OR publish_date > ?) AND state = ? AND updated_date IS NOT NULL AND id NOT IN (?, ?, ?, ?, ?)',
				'values' => array(5, 5, 'ACTIVE', $t, 'ACTIVE', 1, 3, 5, 7, 9)
			)
        );
    }
    
    public function testEmptyArray()
    {
    	$this->_test(
			'id', array(), 
			array(
				'sql' => '`id` AND FALSE', 
				'values' => array()
			)
    	);
    	
    	$this->_test(
			'id !=', array(), 
			array(
				'sql' => '`id` OR TRUE', 
				'values' => array()
			)
    	);
	}
    
    private function _test()
    {
    	$c = new \Snowfire\Database\Query\Conditions();
    	
    	$conditions = array_slice(func_get_args(), 0, -1);
    	call_user_func_array(array($c, 'add'), $conditions);
    	
    	list($expected) = array_slice(func_get_args(), -1);
    	$this->assertEquals($expected, $c->compile());
    }
}
