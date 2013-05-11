<?php

/**
* Copyright 2012, Snowfire AB, snowfireit.com
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

require_once '../library/database/query/helper.php';

class HelperTest extends PHPUnit_Framework_TestCase
{
    public function testIsAssociative()
    {
        $this->assertFalse(
        	Lib\Database\Query\Helper::is_associative(array('a', 'b'))
        );
        
        $this->assertTrue(
        	Lib\Database\Query\Helper::is_associative(array('a' => 'b'))
        );
    }
    
    public function testQuoteColumn()
    {
    	$this->assertEquals(
    		'`column`',
    		Lib\Database\Query\Helper::quote_column('column')
    	);
    	
    	$this->assertEquals(
    		'table.column',
    		Lib\Database\Query\Helper::quote_column('table.column')
    	);
    	
    	$this->assertEquals(
    		'table.column1, table.column2',
    		Lib\Database\Query\Helper::quote_column('table.column1, table.column2')
    	);
    	
    	$this->assertEquals(
    		'table.column1, table.column2',
    		Lib\Database\Query\Helper::quote_column('table.column1, table.column2')
    	);
    	
    	$this->assertEquals(
    		'table.column',
    		Lib\Database\Query\Helper::quote_column('table.column', true)
    	);
    }
    
    /**
    * @expectedException InvalidArgumentException
    */
    public function testQuoteColumnForceOneFail()
    {
		$this->assertEquals(
    		'table.column1, table.column2',
    		Lib\Database\Query\Helper::quote_column('table.column1, table.column2', true)
    	);
    }
}
