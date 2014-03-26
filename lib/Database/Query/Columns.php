<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

namespace Snowfire\Database\Query;

// Can't include with phpunit?
//require_once 'helper.php';

class Columns
{
	private $_data = array();
	
	public function __construct($columns = null)
	{
		if ($columns) {
			$this->add($columns);
		}
	}
	
	/**
	* @param mixed $columns
	* @return Columns
	*/
	public static function create($columns = null)
	{
		if ($columns) {
			return new self($columns);
		} else {
			return new self();
		}
	}
	
	/**
	* @param mixed $columns
	* @return Columns
	*/
	public function &add($columns)
	{
		if (is_array($columns) && !Helper::is_associative($columns)) {
			$this->_data = array_merge($this->_data, $columns);
		} else {
			$this->_data[] = $columns;
		}
		
		return $this;
	}
	
	public function sql()
	{
		$sql = array();
		
		foreach ($this->_data as $columns) {
			if (is_string($columns)) {
				$sql[] = Helper::quote_column($columns);
			} else {
				foreach ($columns as $alias => $column) {
					$sql[] = Helper::quote_column($column) . ' AS ' . Helper::quote_column($alias);
				}
			}
		}
		
		return implode(', ', $sql);
	}
}