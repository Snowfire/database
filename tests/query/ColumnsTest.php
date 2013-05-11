<?php

/**
* Copyright 2012, Snowfire AB, snowfireit.com
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

require_once '../library/database/query/columns.php';

class ColumnsTest extends PHPUnit_Framework_TestCase
{
    public function testAsterix()
    {
    	$c = new Lib\Database\Query\Columns('*');
    	$this->assertEquals(
    		'*',
    		$c->sql()
    	);
	}
	
    public function testCreate()
    {
    	$c = Lib\Database\Query\Columns::create('column');
    	$this->assertEquals(
    		'`column`',
    		$c->sql()
    	);
	}
	
    public function testMixed()
    {
    	$c = new Lib\Database\Query\Columns();
    	$c->add('table.*');
    	$c->add('NOW() AS `now`');
    	$c->add(array('alias' => 'col', 'alias2' => 'col2'));
    	$this->assertEquals(
    		'table.*, NOW() AS `now`, `col` AS `alias`, `col2` AS `alias2`',
    		$c->sql()
    	);
	}
	
    public function testAddMulti()
    {
    	$c = new Lib\Database\Query\Columns();
    	
    	$c->add(array(
    		'table.*',
    		'NOW() AS `now`',
    		array('alias' => 'col', 'alias2' => 'col2')
    	));
    	
    	$this->assertEquals(
    		'table.*, NOW() AS `now`, `col` AS `alias`, `col2` AS `alias2`',
    		$c->sql()
    	);
	}
}
