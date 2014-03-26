<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

namespace Snowfire\Database\Query;

//require 'helper.php';

class Conditions
{
	/**
	* Array with item formatted by:
	* - { col: val, col: val}
	* - [ custom conditions string, parameters ]
	* 
	* @var array
	*/
	private $_conditions = array();
	
	/**
	* Takes optional conditions
	*/
	public function __construct()
	{
		if (func_num_args() > 0) {
			call_user_func_array(array($this, 'add'), func_get_args());
		}
	}
	
	/**
	* @return Conditions
	*/
	public static function create()
	{
		return new self();
	}
	
	/**
	* @return Conditions
	*/
	public function &clean()
	{
		$this->_conditions = array();
		return $this;
	}
	
	/**
	* @return Conditions
	*/
	public function &add()
	{
		$arguments = func_get_args();
		
		if (count($arguments) == 1 && is_array($arguments[0])) {
			if (Helper::is_associative($arguments[0])) {
				// add({ col: val, col: val})
				$this->_conditions[] = $arguments[0];
			} else {
				// add([
				//     [{ col: val, col: val}], 
				//     [custom conditions string, parameters],
				//     [col, val]
				// ])
				
				foreach ($arguments[0] as $arg) {
					call_user_func_array(array($this, 'add'), $arg);
				}
			}
			
		} else if (count($arguments) == 1 && is_string($arguments[0])) {
			// Raw conditions
			$this->_conditions[] = $arguments[0];
			
		} else if (count($arguments) == 2 && is_string($arguments[0])) {
			if (strpos($arguments[0], '?') !== false) {
				// add(custom conditions string, parameters)
				$this->_conditions[] = $arguments;
			} else {
				// add(col, val)
				$this->_conditions[] = array($arguments[0] => $arguments[1]);
			}
			
		} else {
			throw new \BadMethodCallException('Unknown condition type');
		}
		
		return $this;
	}
	
	public function compile()
	{
		$sql = array();
		$values = array();
		
		foreach ($this->_conditions as $condition) {
			// If raw condition
			if (is_string($condition)) {
				$sql[] = $condition;
				
			// If custom condition
			} else if (count($condition) == 2 && !Helper::is_associative($condition)) {
				// Split by '!= ?', '= ?' and '?'
				$parts = preg_split('/((?:!?=\s*)?\?)/', $condition[0], 
					-1, PREG_SPLIT_DELIM_CAPTURE + PREG_SPLIT_NO_EMPTY);
				
				for ($i = 1; $i < count($parts); $i += 2) {
					$is_not = substr($parts[$i], 0, 2) === '!=';
					//$is_equals = substr($parts[$i], 0, 1) === '=';
					$value = $condition[1][floor($i / 2)];
					
					if (is_array($value)) {
						// a IN (1,2,3)
						$values = array_merge($values, $value);
						$parts[$i] = $this->_sql_condition_array_value($value, $is_not);
					} else if ($value === null) {
						// a IS NULL
						$parts[$i] = $this->_sql_condition_null_value($is_not);
					} else {
						// (default) a >= 2
						$values[] = $value;
					}
				}
				
				$sql[] = implode('', $parts);
				
			// Not custom condition
			} else {
				foreach ($condition as $column => $value) {
					if (is_array($value)) {
						// a IN (1,2,3)
					    list($is_not, $quoted_column) = $this->_parse_not_condition($column);
					    $values = array_merge($values, $value);
					    $sql[] = $quoted_column . ' ' . $this->_sql_condition_array_value($value, $is_not);
						
					} else if ($value === null) {
						// a IS NULL
						list($is_not, $quoted_column) = $this->_parse_not_condition($column);
						$sql[] = $quoted_column . ' ' . $this->_sql_condition_null_value($is_not);
					
					} else {
						// (default) a >= 2
						$values[] = $value;
						$sql[] = $this->_parse_condition($column) . ' ?';
					}
				}
			}
		}
		
		return array('sql' => implode(" AND ", $sql), 'values' => $values);
	}
	
	private function _sql_condition_array_value($value, $is_not)
	{
		if (empty($value)) {
			// WHERE a IN () -> WHERE a AND FALSE
			// WHERE a NOT IN () -> WHERE a OR TRUE
			return $is_not ? 'OR TRUE' : 'AND FALSE';
			
		} else {
    		$value = '(' . implode(', ', array_fill(0, count($value), '?')) . ')';
    		return ($is_not ? 'NOT ' : '') . 'IN ' . $value;
		}
	}
	
	private function _sql_condition_null_value($is_not)
	{
		return $is_not ? 'IS NOT NULL' : 'IS NULL';
	}
	
	private function _parse_not_condition($column)
	{
		$is_not = preg_match('/^(.+)\s+!=$/', $column, $m) === 1;
		
		return array(
			$is_not,
			Helper::quote_column($is_not ? $m[1] : $column, true)
		);
	}
	
	private function _parse_condition($column)
	{
		return preg_match('/[!=<>]+| LIKE$/', $column) === 1
			? $column
			: Helper::quote_column($column, true) . ' =';
	}
}