<?php

/**
* Copyright 2012, Snowfire AB, snowfireit.com
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

require_once '../library/database/database.php';

$config = require('../config.php');
$dbconfig = $config['database'];
$dbconfig['dbname'] .= '_test';
$database = new Lib\Database($dbconfig);