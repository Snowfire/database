<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

//require_once __DIR__ . '/../../lib/query/helper.php';
require_once __DIR__ . '/../../vendor/autoload.php';
//require_once __DIR__ . '/../MockDatabase.php';

class HelperTest extends PHPUnit_Framework_TestCase
{
    public function testIsAssociative()
    {
        $this->assertFalse(
        	\Snowfire\Database\Query\Helper::is_associative(array('a', 'b'))
        );
        
        $this->assertTrue(
        	\Snowfire\Database\Query\Helper::is_associative(array('a' => 'b'))
        );
    }
    
    public function testQuoteColumn()
    {
    	$this->assertEquals(
    		'`column`',
    		\Snowfire\Database\Query\Helper::quote_column('column')
    	);
    	
    	$this->assertEquals(
    		'table.column',
    		\Snowfire\Database\Query\Helper::quote_column('table.column')
    	);
    	
    	$this->assertEquals(
    		'table.column1, table.column2',
    		\Snowfire\Database\Query\Helper::quote_column('table.column1, table.column2')
    	);
    	
    	$this->assertEquals(
    		'table.column1, table.column2',
    		\Snowfire\Database\Query\Helper::quote_column('table.column1, table.column2')
    	);
    	
    	$this->assertEquals(
    		'table.column',
    		\Snowfire\Database\Query\Helper::quote_column('table.column', true)
    	);
    }
    
    /**
    * @expectedException InvalidArgumentException
    */
    public function testQuoteColumnForceOneFail()
    {
		$this->assertEquals(
    		'table.column1, table.column2',
    		\Snowfire\Database\Query\Helper::quote_column('table.column1, table.column2', true)
    	);
    }
}
