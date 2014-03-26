<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

namespace Snowfire\Database\Query;

// Can't include with phpunit?
//require_once 'helper.php';

class Assignment
{
	private $_data = array();
	
	public function __construct($data = null)
	{
		if ($data) {
			$this->set($data);
		}
	}
	
	/**
	* @return Assignment
	*/
	public static function create()
	{
		return new self();
	}
	
	/**
	* @param mixed $data
	* @return Assignment
	*/
	public function &set()
	{
		$args = func_get_args();
		
		if (count($args) == 1) {
			if (is_string($args[0])) {
				$this->_data[] = $args[0];
			} else if (Helper::is_associative($args[0])) {
				$this->_data = array_merge($this->_data, $args[0]);
			} else {
				foreach ($args[0] as $params) {
					$params = is_array($params) && !Helper::is_associative($params) ? $params : array($params);
					call_user_func_array(array($this, 'set'), $params);
				}
			}
			
		} else if (count($args) == 2) {
			$this->_data[$args[0]] = $args[1];
			
		} else {
			throw new \InvalidArgumentException();
		}
		
		return $this;
	}
	
	public function compile()
	{
		$sql = array();
		$values = array();
		
		foreach ($this->_data as $column => $value) {
			if (is_array($value)) {
				throw new \InvalidArgumentException("Column \"{$column}\" has an array value");
			}
			
			if (is_numeric($column)) {
				$sql[] = $value;
			} else {
				$sql[] = Helper::quote_column($column) . ' = ?';
				$values[] = $value;
			}
		}
		
		return array('sql' => implode(', ', $sql), 'values' => $values);
	}
}