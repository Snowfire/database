<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

//require_once __DIR__ . '/../MockDatabase.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../MockDatabase.php';

class DeleteTest extends PHPUnit_Framework_TestCase
{
    public function testDelete()
    {
    	$db = new Mock_Database($this, array(
    		array('execute', "DELETE FROM table\nWHERE `column` = ?\nLIMIT 1", array('value'))
    	));
    	
        $q = new \Snowfire\Database\Query($db);
        $q	->delete('table')
	        ->where('column', 'value')
	        ->limit(1)
	        ->execute();
        
        $db->finished();
    }
}
