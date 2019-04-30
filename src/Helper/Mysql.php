<?php

/**
 * Simple and clean database connection class
 */

namespace FaimMedia\MySQLJSONExport\Helper;

use PDO;

use Exception;

class Mysql {

	const DSN_MYSQL_DRIVER = 'mysql';

	protected $_setup = [];
	protected $_pdo;

	/**
	 * Construct and connect
	 */
	public function __construct(array $setup) {

		$this->_setup = $setup;

		$this->connect();
	}

	/**
	 * Connect to database server
	 */
	public function connect(): self {

		$this->disconnect();

		$dsn = $this->buildDSN();

		$this->_pdo = new PDO($dsn, $this->_setup['username'], $this->_setup['password']);

		return $this;
	}

	/**
	 * Disconnect datbase server
	 */
	public function disconnect(): self {
		if($this->_pdo !== null) {
			$this->_pdo = null;
		}

		return $this;
	}

	/**
	 * Build DSN
	 */
	public function buildDSN(): string {

		if(!array_key_exists('username', $this->_setup)) {
			throw new Exception('No username is specified');
		}

		if(!array_key_exists('dbname', $this->_setup)) {
			throw new Exception('No dbname is specified');
		}

		if(!array_key_exists('host', $this->_setup)) {
			$this->_setup['host'] = '127.0.0.1';
		}

		if(!array_key_exists('password', $this->_setup)) {
			$this->_setup['password'] = null;
		}

		$dsn  = self::DSN_MYSQL_DRIVER.':';

		$dsn .= 'host='.$this->_setup['host'].';';
		$dsn .= 'dbname='.$this->_setup['dbname'].';';

		if(array_key_exists('charset', $this->_setup)) {
			$dsn .= 'charset='.$this->_setup['charset'].';';
		}

		return $dsn;
	}

	/**
	 * Execute query
	 */
	public function query($query, $binds = [], int $fetchStyle = PDO::FETCH_ASSOC) {

		$statement = $this->_pdo->prepare($query);
		$statement->execute();

		return $statement->fetchAll($fetchStyle);
	}

/* MAGIC */

	/**
	 * Magic caller
	 */
	public function __call($name, $args) {
		if(is_callable([$this->_pdo, $name])) {
			return call_user_func_array([$this->_pdo, $name], $args);
		}

		throw new Exception('Method `'.$name.'` does not exist in `'.get_class($this->_pdo).'`');
	}
}