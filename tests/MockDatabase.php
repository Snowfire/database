<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

/*require_once __DIR__ . '/../lib/interface.php';
require_once __DIR__ . '/../lib/query/query.php';*/

class Mock_Database implements \Snowfire\DatabaseInterface
{
	private $_testcase;
	
	/**
	* $this->_expected array item:
	* 0: Method (one, many, execute)
	* 1: Query
	* 2: Values / parameters
	* 3: Database class options
	* 4: Optional expected options: return, inserted_id
	*/
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
		//$this->__call('one', array_slice(func_get_args(), 0, func_num_args()));
	}
	
	public function many($sql, $parameters = array(), $option = null)
	{
		return $this->_call('many', $sql, $parameters, $option);
		//$this->__call('many', array_slice(func_get_args(), 0, func_num_args()));
	}
	
	public function execute($sql, $parameters = array())
	{
		return $this->_call('execute', $sql, $parameters);
		//$this->__call('execute', array_slice(func_get_args(), 0, func_num_args()));
	}
	
	/*public function __call($name, $arguments)
	{
		array_splice($arguments, 0, 0, array($name));
		var_dump($arguments);die;
	}*/
	
	private function _call($method, $sql, $parameters, $options = null)
	{
		if ($this->_options['debug']) {
			// Debug
			var_dump(func_get_args());
		} else {
			if (!isset($this->_expected[$this->_call_count])) {
				throw new Exception('Not enough expected calls provided');
			}
			
			// Test this call against what was expected
			$this->_testcase->assertEquals(
				array_slice($this->_expected[$this->_call_count], 0, func_num_args()),
				func_get_args()
			);
		}
		
		// Return null by default
		$extra = array_merge(array(
			'return' => null
		), isset($this->_expected[$this->_call_count][4]) ? $this->_expected[$this->_call_count][4] : array());
		
		// Store optionally provided inserted id, for later retrieval
		if (isset($extra['inserted_id'])) {
			$this->_last_insert_id = $extra['inserted_id'];
		}
		
		// Count all calls so we can check if all expected were made
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