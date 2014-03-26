<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

namespace Snowfire\Database\Model;

use Snowfire\Database\Model;

class Nested extends Model
{
	public function create(array $fields, $foreign_models = array())
	{
		if (isset($fields['next_to_node'])) {
			$transaction = true;
			$fields['left'] = $fields['next_to_node']['right'] + 1;
			$fields['right'] = $fields['left'] + 1;
			
			$this->_transaction_begin();
			$this->_query_update()->set('`left` = `left` + 2')->where('`left` >', $fields['next_to_node']['left'])->execute();
			$this->_query_update()->set('`right` = `right` + 2')->where('`right` >', $fields['next_to_node']['right'])->execute();
			
		
		} else if (isset($fields['inside_node'])) {
			$transaction = true;
			$fields['left'] = $fields['inside_node']['left'] + 1;
			$fields['right'] = $fields['left'] + 1;
			
			$this->_transaction_begin();
			$this->_query_update()->set('`left` = `left` + 2')->where('`left` >', $fields['inside_node']['left'])->execute();
			$this->_query_update()->set('`right` = `right` + 2')->where('`right` >', $fields['inside_node']['left'])->execute();
		
		} else if (!isset($fields['left'], $fields['right'])) {
			$transaction = true;
			$this->_transaction_begin();
			
			$last_right = $this->_query_select('MAX(`right`) AS max_right')->limit(1)
				->execute(array('single_column' => true));
			
			$fields['left'] = $last_right + 1;
			$fields['right'] = $last_right + 2;
		}
		
		unset($fields['next_to_node'], $fields['inside_node']);
		parent::create($fields, $foreign_models);
		
		if ($transaction) {
			$this->_transaction_end();
		}
	}
	
	final protected function _delete_pre(&$rows, &$conditions, &$foreign_models = array())
	{
		throw new \Exception('Not finished');
		
		$this->_transaction_begin();
		
		foreach ($rows as $row) {
			$item = $this->one($row['id']);	// left and right will be changed by this method
			
			if ($item['left'] + 1 != $item['right']) {
				throw new \InvalidArgumentException("No support for deleting non-leaf nodes");
			}
			
			$this->_query_update()->set('`left` = `left` + 2')->where('`left` >', $item['left'])->execute();
			$this->_query_update()->set('`right` = `right` + 2')->where('`right` >', $item['right'])->execute();
		}
		
		$this->_transaction_end();
	}
	
	public function parent_path(array $node)
	{
		return $this->many(array(
			'conditions' => array('`left` <' => $node['left'], '`right` >' => $node['right']),
			'order_by' => '(`right` - `left`)'
		));
	}
	
	/**
	* @param array $p parent_id
	*/
	public function nested($p = array())
	{
		$o = array(
			'order_by' => 'left'
		);
		
		if (isset($p['parent_id'])) {
			$parent = $this->one($p['parent_id']);
			$o['conditions'] = array('`left` >' => $parent['left'], '`right` <' => $parent['right']);
		}
		
		$rows = $this->many($o);
		
		return $this->_nest($rows);
	}
	
	private function _nest($rows)
	{
		$nested = array();
		
		foreach ($rows as &$row) {
			$row['children'] = array();
			$row['parent'] = null;
			
			if ($nested) {
				// Is first child of $last
				if ($last['left'] + 1 == $row['left']) {
					$last['children'][] = &$row;
					$row['parent'] = &$last;
				
				// Is non-first child of any of $last or its parents, or sibling of root nodes
				} else {
					$parent = &$last;
					
					while ($parent['right'] + 1 != $row['left']) {
						if (!$parent['parent']) {
							/*$parent = null;
							break;*/
							/*\Zend_Debug::dump($nested);
							\Zend_Debug::dump($row);
							die;*/
							throw new \Exception('Failed to sort nested data');
						}
						
						$parent = &$parent['parent'];
					}
					
					if ($parent['parent']) {
						$row['parent'] = &$parent['parent'];
						$parent['parent']['children'][] = &$row;
					} else {
						$nested[] = &$row;
					}
				}
				
			} else {
				$nested[] = &$row;
			}
			
			$last = &$row;
			unset($row);
		}
		
		return $this->_drop_parent_key($nested);
	}
	
	private function _drop_parent_key($nested)
	{
		$walk = function ($data) use (&$walk) {
			foreach ($data as &$d) {
				unset($d['parent']);
				$d['children'] = $walk($d['children']);
			}
			
			return $data;
		};
		
		return $walk($nested);
	}
	
	/**
	* [
	* 	{ name: 'A', children: [] }
	* 	{ name: 'B', children: [
	* 		{ name: 'B-A', children: [] }
	* 	] }
	* ]
	* 
	*/
	public function import(array $data)
	{
		var_dump($this->_add_right_left($data));
	}
	
	protected function _add_right_left(array $data, $start_left = 1)
	{
		foreach ($data as &$item) {
			$item['left'] = $start_left;
			
			if ($item['children']) {
				$item['children'] = $this->_add_right_left($item['children'], $start_left + 1);
				$item['right'] = $item['children'][count($item['children']) - 1]['right'] + 1;
			} else {
				$item['right'] = $start_left + 1;
			}
			
			$start_left = $item['right'] + 1;
			unset($item);
		}
		
		return $data;
	}
}