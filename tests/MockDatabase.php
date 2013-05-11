<?php

/**
* Copyright 2012, Snowfire AB, snowfireit.com
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

require_once '../library/database/interface.php';
require_once '../library/database/query/query.php';

class Mock_Database implements \Lib\Database_Interface
{
	private $_testcase;
	private $_expected;
	private $_options;
	private $_call_count = 0;
	private $_last_insert_id = null;
	
	/**
	* @param PHPUnit_Framework_TestCase $testcase
	* @param mixed $expected null to debug
	* @return Mock_Database
	*/
	public function __construct(PHPUnit_Framework_TestCase &$testcase, array $expected, $options = array())
	{
		$this->_testcase = &$testcase;
		$this->_expected = $expected;
		$this->_options = array_merge(array(
			'debug' => false
		), $options);
	}
	
	public function finished()
	{
		if ($this->_options['debug']) {
			die;
		} else {
			$this->_testcase->assertEquals(count($this->_expected), $this->_call_count);
		}
	}
	
	public function one($sql, $parameters = array(), $option = null)
	{
		return $this->_call('one', $sql, $parameters, $option);
	}
	
	public function many($sql, $parameters = array(), $option = null)
	{
		return $this->_call('many', $sql, $parameters, $option);
	}
	
	public function execute($sql, $parameters = array())
	{
		return $this->_call('execute', $sql, $parameters);
	}
	
	private function _call($method, $sql, $parameters, $options = null)
	{
		if ($this->_options['debug']) {
			// Debug
			var_dump(func_get_args());
		} else {
			if (!isset($this->_expected[$this->_call_count])) {
				throw new Exception('Not enough expected calls provided');
			}
			
			$this->_testcase->assertEquals(
				array_slice($this->_expected[$this->_call_count], 0, func_num_args()),
				func_get_args()
			);
		}
		
		$extra = array_merge(array(
			'return' => null
		), isset($this->_expected[$this->_call_count][4]) ? $this->_expected[$this->_call_count][4] : array());
		
		if (isset($extra['inserted_id'])) {
			$this->_last_insert_id = $extra['inserted_id'];
		}
		
		$this->_call_count++;
		
		return $extra['return'];
	}
	
	public function transaction_begin()
	{
		
	}
	
	public function transaction_end()
	{
		
	}
	
	public function last_insert_id()
	{
		return $this->_last_insert_id;
	}
}