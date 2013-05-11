<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

require_once __DIR__ . '/../../lib/query/helper.php';

class HelperTest extends PHPUnit_Framework_TestCase
{
    public function testIsAssociative()
    {
        $this->assertFalse(
        	SF\Database\Query\Helper::is_associative(array('a', 'b'))
        );
        
        $this->assertTrue(
        	SF\Database\Query\Helper::is_associative(array('a' => 'b'))
        );
    }
    
    public function testQuoteColumn()
    {
    	$this->assertEquals(
    		'`column`',
    		SF\Database\Query\Helper::quote_column('column')
    	);
    	
    	$this->assertEquals(
    		'table.column',
    		SF\Database\Query\Helper::quote_column('table.column')
    	);
    	
    	$this->assertEquals(
    		'table.column1, table.column2',
    		SF\Database\Query\Helper::quote_column('table.column1, table.column2')
    	);
    	
    	$this->assertEquals(
    		'table.column1, table.column2',
    		SF\Database\Query\Helper::quote_column('table.column1, table.column2')
    	);
    	
    	$this->assertEquals(
    		'table.column',
    		SF\Database\Query\Helper::quote_column('table.column', true)
    	);
    }
    
    /**
    * @expectedException InvalidArgumentException
    */
    public function testQuoteColumnForceOneFail()
    {
		$this->assertEquals(
    		'table.column1, table.column2',
    		SF\Database\Query\Helper::quote_column('table.column1, table.column2', true)
    	);
    }
}
