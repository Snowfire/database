<?php

namespace Snowfire\Database\Model;

class Container implements \ArrayAccess
{
	private $_storage;

	public function offsetSet($offset, $value)
	{
		$this->_storage[$offset] = $value;
	}

	public function offsetExists($offset)
	{
		return $this->load($offset) !== null;
	}

	public function offsetUnset($offset)
	{
		unset($this->_storage[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->load($offset);
	}

	public function &load($model, $use_original_classnames = false)
	{
		if ($use_original_classnames) {
			preg_replace_callback("/(?:\b|_)[a-z]/", function ($m) { return strtoupper($m[0]); }, $model);
		}

		if (!isset($this->_storage[$model])) {
			$this->_storage[$model] = class_exists($model) ? new $model() : null;
		}

		return $this->_storage[$model];
	}
}