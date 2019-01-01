<?php

namespace FaimMedia\MySQLJSONExport;

use ExportException;

use FaimMedia\MySQLJSONExport\Helper\Mysql,
    FaimMedia\MySQLJSONExport\Traits\DatabaseTrait;

use PDO;

class Compare {
	use DatabaseTrait;

	protected $_isCompared = false;

	public function __construct(Mysql $mysql, Export $export) {

		$fetch = new Fetch($mysql);

		$this->setDatabase($mysql);
		$this->setFetch($fetch);
	}

	/**
	 * Compare database strecture files with connected database structure
	 */
	public function compare() {

		if($this->_isCompared) {
			return false;
		}

		$this->compareStructure();
		$this->compareTriggers();

	}

	/**
	 * Compare structure
	 */
	protected function compareStructure() {

	}

	/**
	 * Compare triggers
	 */
	protected function compareTriggers() {

	}

	/**
	 * Update the database structure mismatches
	 * Use the first argument to also delete obsolete table columns
	 */
	public function updateStructure(bool $deleteColumn = false) {
		$this->compare();


	}
}