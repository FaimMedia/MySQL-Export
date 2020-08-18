<?php

namespace FaimMedia\MySQLJSONExport\Engine;

use FaimMedia\MySQLJSONExport\Engine;

use Exception;

/**
 * Abstract class for Engine classes
 */
abstract class AbstractEngine {

	/**
	 * Final constructor
	 */
	final public function __construct(Engine $engine)
	{
		$this->setEngine($engine);
	}

	/**
	 * Set engine
	 */
	final protected function setEngine(Engine $engine): self
	{
		$this->_engine = $engine;

		return $this;
	}

	/**
	 * Get engine
	 */
	final public function getEngine(): Engine
	{
		return $this->_engine;
	}

	/**
	 * Magic engine call forwarder
	 */
	final public function __call($name, $arguments)
	{
		if(is_callable([$this->getEngine(), $name])) {
			return call_user_func_array([$this->getEngine(), $name], $arguments);
		}

		throw new Exception('Method `'.$name.'` does not exist on instance `'.static::class.'`');
	}

	/**
	 * Magic engine getter
	 */
	final public function __get($name) {
		if($name == 'db') {
			return $this->getEngine()->getDatabase();
		}

		return parent::${$name};
	}
}