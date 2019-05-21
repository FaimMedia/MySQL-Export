<?php

namespace FaimMedia\MySQLJSONExport\Helper;

use Exception;

class Parameters {

	protected $_parameters = [];

	/**
	 * Constructor
	 */
	public function __construct(string $delimiter = ':') {
		global $argv;

		$parameters = array_slice($argv, 1);

		foreach($parameters as $parameter) {
			$split = explode($delimiter, $parameter);

			$this->_parameters[$split[0]] = isset($split[1]) ? $split[1] : true;
		}
	}

	/**
	 * Validate required parameters and throws exception
	 */
	public function validateRequired(): bool {

		foreach(func_get_args() as $arg) {
			if(isset($this->$arg)) {
				continue;
			}

			throw new Exception('Parameter `'.$arg.'` is not defined');
		}

		return true;
	}

	/**
	 * Return an index of all parameters
	 */
	public function getParameters() {
		return $this->_parameters;
	}

	/**
	 * Isset
	 */
	public function isset($key): bool {
		return $this->__isset($key);
	}

	/**
	 * Magic isset method
	 */
	public function __isset($key): bool {
		return (array_key_exists($key, $this->_parameters));
	}

	/**
	 * Magic getters
	 */
	public function __get($name) {
		if(isset($this->_parameters[$name])) {
			return $this->_parameters[$name];
		}

		return null;
	}

	/**
	 * Magic method getters (call)
	 */
	public function __call($name, $argument) {
		if(substr($name, 0, 3) === 'get') {
			$index = lcfirst(substr($name, 3));

			return $this->$index;
		}

		throw new Exception('Method `'.$name.'` does not exists in helper `'.self::class.'`');
	}
}