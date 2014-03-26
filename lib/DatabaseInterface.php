<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

namespace Snowfire;

interface DatabaseInterface
{
	public function one($sql, $parameters = array(), $option = null);
	public function many($sql, $parameters = array(), $option = null);
	public function execute($sql, $parameters = array());
	public function last_insert_id();
}