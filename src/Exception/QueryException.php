<?php

namespace FaimMedia\MySQLJSONExport\Exception;

use Exception;

/**
 * Mysql query exception
 */
class QueryException extends Exception {

	protected $_query;

	/**
	 * Set query
	 */
	public function setQuery(string $query) {
		$this->_query = $query;
	}

	/**
	 * Get query
	 */
	public function getQuery(): ?string {
		return $this->_query;
	}
}
