<?php

/**
* Copyright 2012, Snowfire AB, snowfireit.com
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

require_once 'MockDatabase.php';

class DeleteTest extends PHPUnit_Framework_TestCase
{
    public function testDelete()
    {
    	$db = new Mock_Database($this, array(
    		array('execute', "DELETE FROM table\nWHERE `column` = ?\nLIMIT 1", array('value'))
    	));
    	
        $q = new Lib\Database_Query($db);
        $q	->delete('table')
	        ->where('column', 'value')
	        ->limit(1)
	        ->execute();
        
        $db->finished();
    }
}
