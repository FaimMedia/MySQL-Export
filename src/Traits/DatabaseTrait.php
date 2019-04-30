<?php

namespace FaimMedia\MysqlJSONExport\Traits;

use FaimMedia\MysqlJSONExport\Helper\Mysql,
    FaimMedia\MysqlJSONExport\Fetch;

trait DatabaseTrait {

	protected $_db;
	protected $_fetch;

	/**
	 * Set database instance
	 */
	public function setDatabase(Mysql $db): self {
		$this->_db = $db;
		$this->_db->connect();

		return $this;
	}

	/**
	 * Get database instance
	 */
	public function getDatabase(): Mysql {
		return $this->_db;
	}

	/**
	 * Set fetch instances
	 */
	public function setFetch(Fetch $fetch): self {
		$this->_fetch = $fetch;

		return $this;
	}

	/**
	 * Get fetch instance
	 */
	public function getFetch(): Fetch {
		return $this->_fetch;
	}
}