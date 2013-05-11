<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

namespace SF;

/*require_once 'helper.php';
require_once 'conditions.php';*/

class Database_Query
{
	/**
	* @var Database
	*/
	private $_database;
	private $_parameters;
	
	public function __construct(Database_Interface $database)
	{
		$this->_database = $database;
	}
	
	public function __toString()
	{
		$data = $this->_compile();
		
		foreach ($data['values'] as $v) {
			$data['sql'] = preg_replace('/\?/', $this->_database->quote($v), $data['sql'], 1);
		}
		
		return $data['sql'];
	}
	
	private function _compile()
	{
		if (isset($this->delete)) {
			return $this->_compile_delete();
			
		} else if (isset($this->insert)) {
			return $this->_compile_insert();
		
		} else if (isset($this->select)) {
			return $this->_compile_select();
		
		} else if (isset($this->update)) {
			return $this->_compile_update();
		
		} else {
			return '';
		}
	}
	
	/**
	* @param array $options single_column
	*/
	public function execute(array $options = null)
	{
		$data = $this->_compile();
		
		if (isset($this->select)) {
			if (isset($this->limit) && $this->_parameter_first('limit') == 1) {
				$return = $this->_database->one($data['sql'], $data['values'], $options);
			} else {
				$return = $this->_database->many($data['sql'], $data['values'], $options);
			}
		
		} else {
			$this->_database->execute($data['sql'], $data['values']);
			$return = $this->_database->last_insert_id();
		}
		
		$this->clear();
		return $return;
	}
	
	/**
	* @return Database_Query
	*/
	public function &clear()
	{
		$this->_parameters = array();
		//$this->_adapter->clear();
		return $this;
	}
	
	/**
	* @return Database_Query
	*/
	public function &delete($from_table)
	{
		$this->_parameter('delete', $from_table);
		return $this;
	}
	
	/**
	* @return Database_Query
	*/
	public function &insert($into_table)
	{
		$this->_parameter('insert', $into_table);
		return $this;
	}
	
	/**
	* @param string $columns
	* @param array $options distinct
	* @return Database_Query
	*/
	public function &select($columns = '*', $options = array())
	{
		if (isset($options['distinct'])) {
			$this->_parameter('select_distinct', $options['distinct'] ?: null);
		}
		
		$this->_parameter('select', $columns);
		return $this;
	}
	
	/**
	* @return Database_Query
	*/
	public function &update($table)
	{
		$this->_parameter('update', $table);
		return $this;
	}
	
	/**
	* @return Database_Query
	*/
	public function &from($from)
	{
		$this->_parameter('from', $from);
		return $this;
	}
	
	/**
	* set(array('col' => 1)) or set('col', 1)
	* @return Database_Query
	*/
	public function &set($array)
	{
		$arguments = func_get_args();
		$this->_parameter('set', !$arguments[0] ? null : $arguments);
		return $this;
	}
	
	/**
	* @return Database_Query
	*/
	public function values($values)
	{
		if (Database\Query\Helper::is_associative($values)) {
			$this->_parameter('values', $values);
		} else {
			foreach ($values as $v) {
				$this->_parameter('values', $v);
			}
		}
		
		return $this;
	}
	
	/**
	* @return Database_Query
	*/
	public function &where()
	{
		$args = func_get_args();
		
		if ($args[0] === null) {
			$this->_parameter('where', null);
		} else {
			$this->_parameter('where', $args);
		}
		
		return $this;
	}
	
	/**
	* @return Database_Query
	*/
	public function &join($join, $direction = null)
	{
		$this->_parameter('join', $join ? array('table' => $join, 'direction' => $direction) : null);
		return $this;
	}
	
	/**
	* @return Database_Query
	*/
	public function &on()
	{
		$join = &$this->_parameter_last('join');
		$join['on'][] = func_get_args();
		return $this;
	}
	
	/**
	* @return Database_Query
	*/
	public function &group_by($columns)
	{
		$this->_parameter('group_by', $columns);
		return $this;
	}
	
	/**
	* @return Database_Query
	*/
	public function &order_by($columns, $order = null, $collate = null)
	{
		if (!$columns) {
			$this->_parameter('order_by', null);
			
		} else {
			$columns = is_array($columns) ? $columns : array($columns);
			
			foreach ($columns as $column) {
				$column = is_array($column) ? $column : array(
					'column' => $column,
					'order' => $order,
					'collate' => $collate
				);
				
				$this->_parameter('order_by', $column);
			}
		}
		
		return $this;
	}
	
	/**
	* @return Database_Query
	*/
	public function &limit($limit)
	{
		$this->_parameter('limit', $limit);
		return $this;
	}
	
	/**
	* Get or set a parameter
	*/
	private function &_parameter($key, $value = null)
	{
		// Set new value?
		if (func_num_args() == 2) {
			if ($value === null) {
				unset($this->_parameters[$key]);
			} else {
				$this->_parameters[$key] = isset($this->_parameters[$key]) && $this->_parameters[$key]
					? $this->_parameters[$key] 
					: array();
				
				$this->_parameters[$key][] = $value;
			}
		}
		
		// Always return current value by reference
		return $this->_parameters[$key];
	}
	
	/**
	* Get first value of a parameter
	*/
	private function &_parameter_first($key)
	{
		return $this->_parameters[$key][0];
	}
	
	/**
	* Get last value of a parameter
	*/
	private function &_parameter_last($key)
	{
		return $this->_parameters[$key][count($this->_parameters[$key]) - 1];
	}
	
	public function __isset($key)
	{
		return isset($this->_parameters[$key]);
	}
	
	private function _compile_delete()
	{
		$values = array();
		$sql = 'DELETE FROM ' . implode(', ', $this->_parameter('delete'));
		
		if (isset($this->where)) {
			$where = Database\Query\Conditions::create()
				->add($this->_parameter('where'))
				->compile();
			
			$sql .= "\nWHERE " . $where['sql'];
			$values = array_merge($values, $where['values']);
		}
		
		if (isset($this->limit)) {
			$sql .= "\nLIMIT " . $this->_parameter_first('limit');
		}
		
		return array('sql' => $sql, 'values' => $values);
	}
	
	private function _compile_insert()
	{
		$values = array();
		$sql = 'INSERT INTO ' . implode(', ', $this->_parameter('insert'));
		
		if (isset($this->values)) {
			$insert_values = $this->_parameter('values');
			$columns = array_keys($insert_values[0]);

			$sql .= "\n(" . Database\Query\Columns::create($columns)->sql() . ")";
			$sql .= "\nVALUES";
			$values_sql = array();

			foreach ($insert_values as $v) {
				$values_sql[] = "(" . implode(', ', array_fill(0, count($v), '?')) . ")";
				$values = array_merge($values, array_values($v));
			}
			
			$sql .= "\n\t" . implode(",\n\t", $values_sql);
			
		} else if (isset($this->set)) {
			$set = Database\Query\Assignment::create()
				->set($this->_parameter('set'))
				->compile();
			
			$sql .= "\nSET " . $set['sql'];
			$values = array_merge($values, $set['values']);
		} else {
			$sql .= "\nVALUES ()";
		}
		
		return array('sql' => $sql, 'values' => $values);
	}
	
	private function _compile_select()
	{
		$values = array();
		$sql = 'SELECT ' . (isset($this->select_distinct) ? 'DISTINCT ' : '');
		$sql .= Database\Query\Columns::create($this->_parameter('select'))->sql();
		
		if (isset($this->from)) {
			$sql .= "\nFROM " . implode(', ', $this->_parameter('from'));
		}
		
		if (isset($this->join)) {
			foreach ($this->_parameter('join') as $join) {
				$type = $join['direction'] !== null ? strtoupper($join['direction']) . ' ' : '';
				$sql .= "\n{$type}JOIN " . Database\Query\Columns::create($join['table'])->sql();
				$on = Database\Query\Conditions::create()->add($join['on'])->compile();
				$sql .= "\n\tON " . $on['sql'];
				$values = array_merge($values, $on['values']);
			}
		}
		
		if (isset($this->where)) {
			$where = Database\Query\Conditions::create()
				->add($this->_parameter('where'))
				->compile();
			
			$sql .= "\nWHERE " . $where['sql'];
			$values = array_merge($values, $where['values']);
		}
		
		if (isset($this->group_by)) {
			$sql .= "\nGROUP BY " . Database\Query\Columns::create($this->_parameter('group_by'))->sql();
		}
		
		if (isset($this->order_by)) {
			$sql .= "\nORDER BY ";
			$order_by = array();
			
			foreach ($this->_parameter('order_by') as $column) {
				$s = Database\Query\Columns::create($column['column'])->sql();
				
				if (isset($column['collate'])) {
					$s .= " COLLATE {$column['collate']}";
				}
				
				if (isset($column['order'])) {
					$s .= ' ' . strtoupper($column['order']);
				}
				
				$order_by[] = $s;
			}
			
			$sql .= implode(', ', $order_by);
		}
		
		if (isset($this->limit)) {
			$sql .= "\nLIMIT " . $this->_parameter_first('limit');
		}
		
		return array('sql' => $sql, 'values' => $values);
	}
	
	private function _compile_update()
	{
		$values = array();
		$sql = 'UPDATE ' . implode(', ', $this->_parameter('update'));
		
		if (isset($this->set)) {
			$set = \SF\Database\Query\Assignment::create()
				->set($this->_parameter('set'))
				->compile();
			
			$sql .= "\nSET " . $set['sql'];
			$values = array_merge($values, $set['values']);
		}
		
		if (isset($this->where)) {
			$where = Database\Query\Conditions::create()
				->add($this->_parameter('where'))
				->compile();
			
			$sql .= "\nWHERE " . $where['sql'];
			$values = array_merge($values, $where['values']);
		}
		
		return array('sql' => $sql, 'values' => $values);
	}
	
	private function _sqlKeyValueArray($array)
	{
		$sql = array();
		
		foreach ($array as $key => $value) {
			$sql[] = "`{$key}` = " . $this->_adapter->quote($value);
		}
		
		return implode(', ', $sql);
	}
	
	private function _sqlColumns($array)
	{
	    if (is_string($array)) {
	        return $array;
	    }
	    
		$columns = array();
		
		if (Core_Query_Helper::isAssociative($array)) {
			$array = array($array);
		}
		
		foreach ($array as $item) {
			if (is_string($item)) {
				$columns[] = Core_Query_Helper::quoteColumn($item);
			} else {
				$tmp = array();
				
				foreach ($item as $key => $column) {
					$tmp[] = $column . " AS `{$key}`";
				}
				
				$columns[] = implode(', ', $tmp);
			}
		}
		
		return implode(', ', $columns);
	}
}
