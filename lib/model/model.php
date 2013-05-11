<?php

/**
* Copyright 2012, Snowfire AB, snowfireit.com
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

namespace Lib;

/**
* protected static $_required_parameters
* protected static $_table
* protected static $_default_all_parameters
*/

class Database_Model
{
	/**
	* @var Database
	*/
	private static $_database_register;

	/**
	 * @var Database_Model_Container
	 */
	public static $container;
	private static $_common_dependencies = array();
	private $_dependencies = array();

	/**
	* @var Database
	*/
	private $_database;
	private $_parameters;
	private $_parameters_sql;
	
	private $_table;
	private $_singular;
	private $_required_parameters;
	private $_foreign = array();
	private $_special_fields = array();
	private $_active_condition;
	private $_primary_key;

    /**
     * @param array $parameters Base values/conditions used for create, find, edit and delete
     */
    public function __construct(array $parameters = array())
	{
		$called_class = get_called_class();
		$this->_database = &self::$_database_register;
		$this->_required_parameters = isset($called_class::$_required_parameters) ? $called_class::$_required_parameters : array();
		$this->_table = $called_class::table();
		$this->_singular = $called_class::singular();
		$this->_primary_key = $called_class::primary_key();
		
		$missing_required = array_diff($this->_required_parameters, array_keys($parameters));
		
		if ($missing_required) {
			throw new \InvalidArgumentException('Parameters "' 
				. implode('", "', $missing_required) . '" are required and missing');
		}
		
		$this->_parameters = $parameters;
		
		foreach ($parameters as $col => $val) {
			$this->_parameters_sql[$this->_table . '.' . $col] = $val;
		}
		
		
		$this->_foreign = array('one_to_one' => array(), 'one_to_many' => array(), 'many_to_many' => array(), 'many_to_one' => array());
		
		if (isset($called_class::$_foreign)) {
			foreach ($called_class::$_foreign as $foreign) {
				$this->_foreign[$foreign['type']][] = $foreign;
			}
		}
		
		$this->_special_fields['datetime'] = array();
		
		if (isset($called_class::$_special_fields)) {
			$this->_special_fields = $called_class::$_special_fields;
			
			if (isset($this->_special_fields['create_date'])) {
				$this->_special_fields['datetime'][] = $this->_special_fields['create_date'];
			}
		}
		
		if (isset($called_class::$_active_condition)) {
			$this->_active_condition = $called_class::$_active_condition;
		}
	}

	public function __call($function, $args)
	{
		if (preg_match('/^many_by_(.+)$/', $function, $m) == 1) {
			$p = isset($args[1]) ? $args[1] : array();
			$p['conditions'] = array($m[1] => $args[0]);
			return $this->many($p);
		}
		//var_dump(, $args);die;
	}

	public static function database(Database_Interface &$database)
	{
		self::$_database_register = &$database;
	}

	public static function &common_dependency($name, &$value = null)
	{
		if (func_num_args() == 2) {
			self::$_common_dependencies[$name] = &$value;
		} else if (!isset(self::$_common_dependencies[$name])) {
			self::$_common_dependencies[$name] = null;
		}

		return self::$_common_dependencies[$name];
	}

	public function &dependency($name, &$value = null)
	{
		if (func_num_args() == 2) {
			$this->_dependencies[$name] = &$value;
		} else if (!isset(self::$_common_dependencies[$name]) && !isset($this->_dependencies[$name])) {
			$this->_dependencies[$name] = null;
		}

		if (isset(self::$_common_dependencies[$name])) {
			return self::$_common_dependencies[$name];
		} else {
			return $this->_dependencies[$name];
		}
	}

	public static function table()
	{
		$called_class = get_called_class();
		return isset($called_class::$_table) ? $called_class::$_table : strtolower($called_class);
	}
	
	public static function plural()
	{
		$called_class = get_called_class();
		return isset($called_class::$_plural) ? $called_class::$_plural : $called_class::table();
	}

	public static function singular()
	{
		$called_class = get_called_class();
		return isset($called_class::$_singular) ? $called_class::$_singular : null;
	}

	public static function primary_key()
	{
		$called_class = get_called_class();
		return isset($called_class::$_primary_key) ? $called_class::$_primary_key : 'id';
	}
	
	protected function _create_pre(array &$fields, &$foreign_models = array()) {}
	protected function _create_post($id, array &$fields, &$foreign_models = array()) {}
	protected function _delete_pre(&$rows, &$conditions, &$foreign_models = array()) {}
	protected function _delete_post($rows, $conditions, $foreign_models = array()) {}
	protected function _edit_pre(&$conditions, array &$fields) {}
	
	private function _convert_special_fields($fields)
	{
		foreach ($this->_special_fields['datetime'] as $column) {
			if (isset($fields[$column]) && is_numeric($fields[$column])) {
				$fields[$column] = $this->_database->format_date_time($fields[$column]);
			}
		}
		
		return $fields;
	}

	public function create_unique(array $fields)
	{
		if (!$this->exists($fields)) {
			return $this->create($fields);
		} else {
			return null;
		}
	}
	
	public function create(array $fields, $foreign_models = array())
	{
		$this->_create_pre($fields, $foreign_models);
		
		foreach ($this->_foreign['one_to_one'] as $foreign) {
			if (isset($foreign_models[$foreign['name']])) {
				$fields = $this->_create_one_to_one($fields, $foreign_models[$foreign['name']], $foreign);
			}
		}
		
		$foreign_ids = array();
		
		foreach ($this->_foreign['many_to_many'] as $foreign) {
			if (isset($foreign_models[$foreign['name']])) {
				//throw new \InvalidArgumentException("Requires foreign model \"{$foreign['name']}\"");
				$field_name = $foreign_models[$foreign['name']]->singular() . '_ids';
				
				if (isset($fields[$field_name])) {
					$foreign_ids[$foreign['name']] = $fields[$field_name];
					unset($fields[$field_name]);
				}
			}
		}
		
		if (isset($this->_special_fields['create_date']) && !isset($fields[$this->_special_fields['create_date']])) {
			$fields[$this->_special_fields['create_date']] = time();
		}
		
		$fields = $this->_convert_special_fields($fields);
		
		$id = $this->_query_insert()
			->set($fields)
			->execute();

		foreach ($this->_foreign['many_to_many'] as $foreign) {
			if (isset($foreign_ids[$foreign['name']])) {
				$fields = $this->_create_many_to_many($id, $foreign_ids[$foreign['name']], $foreign_models[$foreign['name']]);
			}
		}

		$this->_create_post($id, $fields, $foreign_models);
		return $id;
	}
	
	private function _create_many_to_many($id, $foreign_ids, Database_Model $foreign_model)
	{
		$foreign_singular = $foreign_model->singular();
		
		$table = $this->_table . '_' . $foreign_model->table();
		$this->_transaction_begin();
		
		foreach ($foreign_ids as $foreign_id) {
			$this->_query_raw()->insert($table)->set(array(
				$this->_singular . '_id' => $id,
				$foreign_singular . '_id' => $foreign_id
			))->execute();
		}
		
		$this->_transaction_end();
	}
	
	private function _create_one_to_one($fields, Database_Model $foreign_model, $foreign)
	{
		$foreign_singular = isset($foreign['singular']) ? $foreign['singular'] : $foreign_model->singular();
		
		if (isset($fields[$foreign_singular])) {
			//throw new \InvalidArgumentException("Missing \"{$foreign_singular}\" data");
			$foreign_id = $foreign_model->create($fields[$foreign_singular]);
			unset($fields[$foreign_singular]);
			$fields["{$foreign_singular}_id"] = $foreign_id;
		}
		
		return $fields;
	}
	
	public function edit($conditions, array $fields)
	{
		if (is_numeric($conditions)) {
			$conditions = array('id' => $conditions);
		}
		
		$fields = $this->_convert_special_fields($fields);
		$this->_edit_pre($conditions, $fields);
		$this->_query_update()->set($fields)->where($conditions)->execute();
	}
	
	public function delete($conditions = null, $foreign_models = array())
	{
		if (is_numeric($conditions)) {
			$conditions = array('id' => $conditions);
		}
		
		$rows = $this->_query_select()->where($conditions)->execute();
		$this->_delete_pre($rows, $conditions, $foreign_models);

		foreach ($this->_foreign['many_to_many'] as $foreign) {
			if (!isset($foreign_models[$foreign['name']])) {
				throw new \InvalidArgumentException("Requires foreign model \"{$foreign['name']}\"");
			} else {
				$this->_delete_many_to_many($rows, $foreign_models[$foreign['name']]);
			}
		}
		
		$this->_query_delete()->where($conditions)->execute();
		
		foreach ($this->_foreign['one_to_one'] as $foreign) {
			if (!isset($foreign_models[$foreign['name']])) {
				throw new \InvalidArgumentException("Requires foreign model \"{$foreign['name']}\"");
			} else {
				$this->_delete_one_to_one($rows, $foreign_models[$foreign['name']], $foreign);
			}
		}
		
		$this->_delete_post($rows, $conditions, $foreign_models);
	}
	
	private function _delete_one_to_one($rows, Database_Model $foreign_model, array $foreign)
	{
		$foreign_singular = isset($foreign['singular']) ? $foreign['singular'] : $foreign_model->singular();
		$ids = array();
		
		foreach ($rows as $row) {
			$ids[] = $row["{$foreign_singular}_id"];
		}
		
		$foreign_model->delete(array('id' => $ids));
	}
	
	private function _delete_many_to_many($rows, Database_Model $foreign_model)
	{
		$ids = array();
		
		foreach ($rows as $row) {
			$ids[] = $row['id'];
		}
		
		$foreign_table = $this->_table . '_' . $foreign_model->table();
		$this->_query_raw()->delete($foreign_table)->where($this->_singular . '_id', $ids)
			->execute();
	}
	
	public function exists($conditions = array())
	{
		return $this->one(array('conditions' => $conditions, 'limit' => 1, 'columns' => 'COUNT(*) AS `count`', 'single_column' => true)) !== '0';
	}
	
	/**
	* @param int|array $p id or [same as Model::many()]
	*/
	public function one($p = array())
	{
		if (is_numeric($p)) {
			$p = array('conditions' => array($this->_primary_key => $p));
		}
		
		$p['limit'] = 1;
		return $this->many($p);
	}
	
	/**
	* @param array $p columns, conditions, limit, order_by, id_indexed, value_column, ignore_active_condition, single_column, foreign_models
	*/
	public function many($p = array())
	{
		$called_class = get_called_class();
		
		if (isset($called_class::$_many_default_parameters)) {
			$p = array_merge($called_class::$_many_default_parameters, $p);
		}
		
		if (isset($p['columns'])) {
			$q = $this->_query_select($p['columns']);
		} else {
			$q = $this->_query_select($this->_get_special_datetime_columns_sql($this->_table . '.*', $this->_special_fields));
		}
		
		if (isset($this->_active_condition) && empty($p['ignore_active_condition'])) {
			$q->where($this->_active_condition);
		}
		
		if (isset($p['conditions']) && $p['conditions']) {
			if (is_string($p['conditions'])) {
				$q->where($p['conditions']);
			} else if (is_array($p['conditions']) && Database\Query\Helper::is_associative($p['conditions'])) {
				$q->where($p['conditions']);
			} else {
				call_user_func_array(array($q, 'where'), $p['conditions']);
			}
		}
		
		if (isset($p['limit'])) {
			$q->limit($p['limit']);
		}
		
		if (isset($p['order_by'])) {
			$q->order_by($p['order_by']);
		}
		
		if (isset($p['foreign_models']) && $this->_foreign['many_to_many']) {
			$q->group_by($this->_primary_key);
			
			foreach ($this->_foreign['many_to_many'] as $foreign) {
				if (isset($p['foreign_models'][$foreign['name']])) {
					$this->_get_many_to_many_pre($q, $p['foreign_models'][$foreign['name']]);
				}
			}
		}

		$single_column = isset($p['single_column']) && $p['single_column'];
		$rows = $q->execute($single_column ? array('single_column' => $single_column) : null);
		$single_row = isset($p['limit']) && $p['limit'] == 1;
		
		if ($rows && isset($p['foreign_models']) && !$single_column) {
			foreach ($this->_foreign['one_to_many'] as $foreign) {
				if (isset($p['foreign_models'][$foreign['name']])) {
					$rows = $this->_get_one_to_many($single_row, $rows, $p['foreign_models'][$foreign['name']]);
				}
			}
			
			foreach ($this->_foreign['one_to_one'] as $foreign) {
				if (isset($p['foreign_models'][$foreign['name']])) {
					$rows = $this->_get_one_to_one($single_row, $rows, $p['foreign_models'][$foreign['name']], $foreign);
				}
			}
			
			foreach ($this->_foreign['many_to_many'] as $foreign) {
				if (isset($p['foreign_models'][$foreign['name']])) {
					$rows = $this->_get_many_to_many_post($single_row, $rows, $p['foreign_models'][$foreign['name']]);
				}
			}
			
			foreach ($this->_foreign['many_to_one'] as $foreign) {
				if (isset($p['foreign_models'][$foreign['name']])) {
					$rows = $this->_get_many_to_one($single_row, $rows, $p['foreign_models'][$foreign['name']], $foreign);
				}
			}
		}
		
		if (!$single_row && !$single_column && (isset($p['id_indexed']) || isset($p['value_column']))) {
			$result = array();
			
			foreach ($rows as $row) {
				$v = isset($p['value_column']) ? $row[$p['value_column']] : $row;
				
				if (isset($p['id_indexed'])) {
					$result[$row[$this->_primary_key]] = $v;
				} else {
					$result[] = $v;
				}
			}
			
			return $result;
		} else {
			return $rows;
		}
	}
	
	public function count($conditions = array())
	{
		$q = $this->_query_select('COUNT(*) AS count');
		
		if (count($conditions) > 0) {
			$q->where($conditions);
		}
		
		$row = $q->execute();
		
		return $row[0]['count'];
	}
	
	private function _get_special_datetime_columns_sql($columns, $special_fields)
	{
		//$columns = !is_array($columns) ? array($columns) : $columns;
		$special_fields['datetime'] = isset($special_fields['datetime']) ? $special_fields['datetime'] : array();
		
		foreach ($special_fields['datetime'] as $field) {
			$columns .= ", UNIX_TIMESTAMP({$this->_table}.{$field}) AS `{$field}`";
		}
		
		return $columns;
	}
	
	/**
	* people: id, passport_id
	* passports: id
	*/
	private function _get_one_to_one($single_row, $rows, Database_Model $model, array $foreign)
	{
		$called_class = get_called_class();
		$ids = array();
		$index = array();
		$foreign_singular = isset($foreign['singular']) ? $foreign['singular'] : $model->singular();
		$local_column = $foreign_singular . '_id';
		
		if ($single_row) {
			$ids = $rows[$local_column];
			$rows[$foreign_singular] = null;
			unset($rows[$local_column]);
		} else {
			foreach ($rows as &$row) {
				$ids[] = $row[$local_column];
				$index[$row[$local_column]] = &$row;
				$row[$foreign_singular] = null;
				unset($row[$local_column]);
				unset($row);
			}
		}
		
		$foreign_rows = $model->many(array(
			'conditions' => array('id' => $ids),
			'limit' => $single_row ? 1 : null
		));
		
		if ($single_row) {
			$rows[$foreign_singular] = $foreign_rows;
		} else {
			foreach ($foreign_rows as $r) {
				$index[$r['id']][$foreign_singular] = $r;
			}
		}
		
		return $rows;
	}
	
	/**
	* people: id, passport_id
	* passports: id
	*/
	private function _get_many_to_one($single_row, $rows, Database_Model $model, array $foreign)
	{
		$called_class = get_called_class();
		$ids = array();
		$index = array();
		$foreign_singular = isset($foreign['singular']) ? $foreign['singular'] : $model->singular();
		$local_column = $foreign_singular . '_id';
		
		if ($single_row) {
			$ids = $rows[$local_column];
			$rows[$foreign_singular] = null;
			unset($rows[$local_column]);
		} else {
			foreach ($rows as &$row) {
				$ids[] = $row[$local_column];
				$index[$row[$local_column]] = &$row;
				$row[$foreign_singular] = null;
				unset($row[$local_column]);
				unset($row);
			}
		}
		
		$foreign_rows = $model->many(array(
			'conditions' => array('id' => $ids),
			'limit' => $single_row ? 1 : null
		));
		
		if ($single_row) {
			$rows[$foreign_singular] = $foreign_rows;
		} else {
			foreach ($foreign_rows as $r) {
				$index[$r['id']][$foreign_singular] = $r;
			}
		}
		
		return $rows;
	}
	
	/**
	* products: id
	* products_options: id | product_id
	*/
	private function _get_one_to_many($single_row, $rows, Database_Model $foreign_model)
	{
		$called_class = get_called_class();
		$foreign_plural = $foreign_model->plural();
		$ids = array();
		$index = array();
		
		if ($single_row) {
			$ids = $rows['id'];
			$rows[$foreign_plural] = array();
		} else {
			foreach ($rows as &$row) {
				$row[$foreign_plural] = array();
				$ids[] = $row['id'];
				$index[$row['id']] = &$row;
				unset($row);
			}
		}
		
		if ($this->_singular === null) {
			throw new \Exception("Singular must be defined on \"{$called_class}\"");
		}
		
		$foreign_table_local_id = $this->_singular . '_id';
		
		$foreign_rows = $foreign_model->many(array(
			'conditions' => array($foreign_table_local_id => $ids)
		));
		
		if ($single_row) {
			$rows[$foreign_plural] = $foreign_rows;
		} else {
			foreach ($foreign_rows as $r) {
				$index[$r[$foreign_table_local_id]][$foreign_plural][] = $r;
			}
		}
		
		return $rows;
	}
	
	private function _get_many_to_many_pre(Database_Query &$query, Database_Model $foreign_model)
	{
		$foreign_table = $this->_table . '_' . $foreign_model->table();
		$foreign_singular = $foreign_model->singular();
		$local_column = $foreign_table . '.' . $this->_singular . '_id';
		$foreign_column = $foreign_table . '.' . $foreign_singular . '_id';
		
		$query->join($foreign_table, 'left')->on($local_column . ' = id');
		$query->select('GROUP_CONCAT(DISTINCT ' . $foreign_column . ') AS ' . $foreign_singular . '_ids');
		//echo $query;die;
	}
	
	private function _get_many_to_many_post($single_row, $rows, Database_Model $foreign_model)
	{
		$foreign_singular = $foreign_model->singular();
		$col = $foreign_singular . '_ids';
		$foreign_plural = $foreign_model->plural();
		$foreign_ids = array();
		$index = array();
		
		if ($single_row) {
			$foreign_ids = $rows[$col] ? explode(',', $rows[$col]) : array();
			$rows[$foreign_plural] = array();
			unset($rows[$col]);
		} else {
			foreach ($rows as &$row) {
				if ($row[$col]) {
					foreach (explode(',', $row[$col]) as $id) {
						$foreign_ids[] = $id;
						$index[$id][] = &$row;
					}
				}
				
				$row[$foreign_plural] = array();
				unset($row[$col], $row);
			}
		}
		
		$foreign_rows = $foreign_model->many(array(
			'conditions' => array('id' => $foreign_ids)
		));
		
		if ($single_row) {
			$rows[$foreign_plural] = $foreign_rows;
		} else {
			foreach ($foreign_rows as $row) {
				foreach ($index[$row['id']] as &$r) {
					$r[$foreign_plural][] = $row;
					unset($r);
				}
			}
		}
		
		return $rows;
	}
	
	/*private function _rows_to_ids_and_id_index($single_row, $rows, &$ids, &$index)
	{
		$ids = array();
		$index = array();
		
		if ($single_row) {
			$ids = $rows[$local_column];
			unset($rows[$local_column]);
		} else {
			foreach ($rows as &$row) {
				$ids[] = $row[$local_column];
				$index[$row[$local_column]] = &$row;
				unset($row[$local_column]);
				unset($row);
			}
		}
	}*/
	
	/**
	* @return Database_Query
	* 
	*/
	protected function _query_raw()
	{
		return new Database_Query($this->_database);
	}
	
	protected function _query_select()
	{
		$q = $this->_query_raw();
		call_user_func_array(array($q, 'select'), func_get_args());
		return $q->from($this->_table)->where($this->_parameters_sql);
	}
	
	protected function _query_update()
	{
		return $this->_query_raw()->update($this->_table)->where($this->_parameters_sql);
	}
	
	protected function _query_insert()
	{
		return $this->_query_raw()->insert($this->_table)->set($this->_parameters_sql);
	}
	
	protected function _query_delete()
	{
		return $this->_query_raw()->delete($this->_table)->where($this->_parameters_sql);
	}
	
	protected function _get_parameters($name)
	{
		return $this->_parameters[$name];
	}
	
	protected function _transaction_begin()
	{
		$this->_database->transaction_begin();
	}
	
	protected function _transaction_end()
	{
		$this->_database->transaction_end();
	}
}