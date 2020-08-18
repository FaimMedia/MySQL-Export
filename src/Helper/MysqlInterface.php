<?php

namespace FaimMedia\MySQLJSONExport\Helper;

/**
 * Mysql interface
 */
interface MysqlInterface {

	/**
	 * Connect method
	 */
	public function connect();

	/**
	 * Disconnect method
	 */
	public function disconnect();

	/**
	 * Query method
	 */
	public function query(string $query, array $binds, int $fetchStyle);
}