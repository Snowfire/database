<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

require_once __DIR__ . '/../MockDatabase.php';

class SelectTest extends PHPUnit_Framework_TestCase
{
    public function testSelect()
    {
    	$db = new Mock_Database($this, array(
    		array('many', "SELECT *\nFROM table", array(), null),
    		array('many', "SELECT `column`\nFROM table", array(), null),
    		array('one', 
    			"SELECT DISTINCT *\n"
    			. "FROM table\n"
    			. "LEFT JOIN `table2`\n"
    			. "\tON table2.id = table.foreign_id AND table2.state = ?\n"
    			. "WHERE table.id = ? AND table.id = ?\n"
    			. "GROUP BY `column`\n"
    			. "ORDER BY `created_date` DESC, `id`, `name` COLLATE utf8_swedish_ci DESC\n"
    			. "LIMIT 1", 
    		array('ACTIVE', 9, 10), null)
    	));
    	
        $q = new SF\Database_Query($db);
        
        // Basic query functionality 
        $q->select()->from('table')->execute();
        
        // Clear query param
        $q->select()->select(null)->select('column')
        	->from('table')->execute();
        
        // Full select feature test
        $q
        	->select('*', array('distinct' => true))
        	->from('table')
        	->join('table2', 'left')
        		->on('table2.id = table.foreign_id')
        		->on('table2.state', 'ACTIVE')
        	->where('table.id', 8)
        	->where(null)
        	->where('table.id', 9)
        	->where('table.id', 10)
        	->group_by('column')
        	->order_by('created_date', 'desc')
        	->order_by(array('id', array('column' => 'name', 'order' => 'desc', 'collate' => 'utf8_swedish_ci')))
        	->limit(1)->execute();
        
        $db->finished();
    }
}
