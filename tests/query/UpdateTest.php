<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

require_once __DIR__ . '/../MockDatabase.php';

class UpdateTest extends PHPUnit_Framework_TestCase
{
    public function testUpdate()
    {
    	$db = new Mock_Database($this, array(
    		array('execute', "UPDATE table\nSET `column` = ?, `column2` = ?, `column3` = ?\n"
    			. "WHERE `column` = ?", array('value', 2, 3, 9))
    	));
    	
        $q = new SF\Database_Query($db);
        $q	->update('table')
	        ->set('column', 'value')
	        ->set(array(
        		'column2' => 2,
        		'column3' => 3
	        ))
	        ->set(array(
        		'column' => 'value'
	        ))
	        ->where('column', 9)
	        ->execute();
        
        $db->finished();
    }
}
