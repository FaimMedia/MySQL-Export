#!/usr/bin/env php
<?php

use FaimMedia\MySQLJSONExport\Export,
	FaimMedia\MySQLJSONExport\Helper\Mysql,
    FaimMedia\MySQLJSONExport\Helper\Parameters;

require dirname(__DIR__, 4) . '/vendor/autoload.php';

$parameters = new Parameters();

var_dump($parameters->getParameters());

$parameters->validateRequired('host', 'database', 'username', 'password');

$mysql = new Mysql([
	'host'     => $parameters->getHost(),
	'dbname'   => $parameters->getDatabase(),
	'username' => $parameters->getUsername(),
	'password' => $parameters->getPassword(),

	'charset'  => 'utf8mb4',
]);

$export = new Export($mysql);

var_dump($export->getTriggersArray());

var_dump($export->getTablesArray());