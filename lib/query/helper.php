<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

namespace SF\Database\Query;

class Helper
{
	public static function is_associative($array)
	{
		if (is_array($array) && !empty($array)) {
			return !is_numeric(implode('', array_keys($array)));
		}
		
		return false;
	}
	
	public static function quote_column($column, $forceOneColumn = false)
	{
		if (preg_match('/^[a-z0-9_-]+$/i', $column) == 1) {
			return "`{$column}`";
		} else if (preg_match('/^[a-z0-9_\.-]+$/i', $column) == 1) {
			// `table.column` is not valid, but is only one column
			return "{$column}";
		} else {
		    if ($forceOneColumn) {
		        throw new \InvalidArgumentException("Column '{$column}' should only be one column");
		    } else {
				return $column;
		    }
		}
	}
	
	public static function option($option, $options)
	{
		return ($options & $option) == $option;
	}
	
	public static function datetime($unixtime)
	{
	    return date('Y-m-d H:i:s', $unixtime);
	}
}