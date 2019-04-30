#!/usr/bin/env php
<?php

use Phalcon\Db\Adapter\Pdo\Mysql;

use FaimMedia\MySQLJSONExport\Engine,
    FaimMedia\MySQLJSONExport\Helper\Parameters;

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

if($parameters->__isset('--export')) {
	$engine->export();
} else {
	$engine->compare();
}
}
