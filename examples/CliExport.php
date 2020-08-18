#!/usr/bin/env php
<?php

use FaimMedia\MySQLJSONExport\{
	Engine,
	Helper\Parameters,
	Helper\Mysql
};

require dirname(__DIR__, 4) . '/vendor/autoload.php';

$parameters = new Parameters();
$parameters->validateRequired('host', 'database', 'username', 'password');

$mysql = new Mysql([
	'host'     => $parameters->getHost(),
	'dbname'   => $parameters->getDatabase(),
	'username' => $parameters->getUsername(),
	'password' => $parameters->getPassword(),

	'charset'  => 'utf8mb4',
]);

$engine = new Engine($mysql, 'cache/');

if($parameters->isset('export')) {
	$engine->export();
} else {

	if($parameters->isset('auto-update')) {
		$engine->getCompare()->setAutoUpdate(true);
	}

	if($parameters->isset('auto-delete')) {
		$engine->getCompare()->setAutoDelete(true);
	}

	$engine->compare();
}
