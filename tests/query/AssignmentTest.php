<?php

/**
* Copyright 2012, Snowfire AB, snowfireit.com
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

require_once '../library/database/query/assignment.php';

class AssignmentTest extends PHPUnit_Framework_TestCase
{
    public function testMixed()
    {
    	$c = new Lib\Database\Query\Assignment();
    	
    	// Different styles
    	$c->set(array(
    		array('column', 'value'),
    		array(array('column2' => 'value2', 'column3' => 'value3')),
    		array('custom = column + 1')
    	));
    	
    	// Overwrite
    	$c->set('column', 'value');
    	$c->set(array('column2' => 'value2', 'column3' => 'value3'));
    	
    	$this->assertEquals(
    		array('sql' => '`column` = ?, `column2` = ?, `column3` = ?, custom = column + 1', 'values' => array('value', 'value2', 'value3')),
    		$c->compile()
    	);
	}
	
	public function testShorter()
	{
		$c = new Lib\Database\Query\Assignment();
		
		$c->set(array(
    		array('column', 'value'),
    		array('column2' => 'value2', 'column3' => 'value3'),
    		'custom = column + 1'
    	));
    	
    	$this->assertEquals(
    		array('sql' => '`column` = ?, `column2` = ?, `column3` = ?, custom = column + 1', 'values' => array('value', 'value2', 'value3')),
    		$c->compile()
    	);
	}
}
