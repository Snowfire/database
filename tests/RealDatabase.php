<?php

/**
* Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

require_once __DIR__ . '/../lib/database.php';

$config = require('../config.php');
$dbconfig = $config['database'];
$dbconfig['dbname'] .= '_test';
$database = new SF\Database($dbconfig);