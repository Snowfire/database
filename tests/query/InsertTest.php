<?php

/**
* Copyright 2012, Snowfire AB, snowfireit.com
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

require_once 'MockDatabase.php';

class InsertTest extends PHPUnit_Framework_TestCase
{
    public function testInsert()
    {
    	$db = new Mock_Database($this, array(
    		array('execute', "INSERT INTO table\nSET `column` = ?, `column2` = ?, `column3` = ?", array('value', 2, 3))
    	));
    	
        $q = new Lib\Database_Query($db);
        $q	->insert('table')
	        ->set('column', 'value')
	        ->set(array(
        		'column2' => 2,
        		'column3' => 3
	        ))
	        ->set(array(
        		'column' => 'value'
	        ))
	        ->execute();
        
        $db->finished();
    }
    
    public function testValues()
    {
    	$db = new Mock_Database($this, array(
    		array('execute', "INSERT INTO table\n(`name`, `state`)\nVALUES\n\t(?, ?),\n\t(?, ?)", array('name1', 'state1', 'name2', 'state2'))
    	));
    	
        $q = new Lib\Database_Query($db);
        $q	->insert('table')
	        ->values(array(
	        	array('name' => 'name1', 'state' => 'state1'),
	        	array('name' => 'name2', 'state' => 'state2')
	        ))
	        ->execute();
        
        $db->finished();
    }
}
