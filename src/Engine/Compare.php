<?php

namespace FaimMedia\MySQLJSONExport\Engine;

use Phalcon\Db;

use FaimMedia\MySQLJSONExport\Engine\AbstractEngine;

use FaimMedia\MySQLJSONExport\Helper\MysqlSyntax;

use FaimMedia\MySQLJSONExport\Exception\CompareException;

/**
 * Compare database data
 */
class Compare extends AbstractEngine {

	protected $_autoUpdate = true;

	protected $_exportDatabaseArray;
	protected $_exportFileArray;

	/**
	 * Set auto update
	 * If on, the engine will try to automatically database mismatches
	 */
	public function setAutoUpdate(bool $autoUpdate): self {
		$this->_autoUpdate = $autoUpdate;

		return $this;
	}

	/**
	 * Get auto update
	 */
	public function getAutoUpdate(): bool {
		return $this->_autoUpdate;
	}

	/**
	 * Get export array from database
	 */
	public function getExportDatabaseArray(): array {

		if($this->_exportDatabaseArray === null) {
			$this->_exportDatabaseArray = $this->getEngine()->getExport()->getTablesArray();
		}

		return $this->_exportDatabaseArray;
	}

	/**
	 * Get export array from export file
	 */
	public function getExportFileArray(): array {

		if($this->_exportFileArray === null) {
			$this->_exportFileArray = $this->getEngine()->getExport()->readTablesExportFile();
		}

		return $this->_exportFileArray;
	}

	/**
	 * Compare export file with current database connection
	 */
	public function compareAll() {

		$this->compareTables();
		$this->compareTableAttributes();
		$this->compareTableFields();
		$this->compareTableFieldAttributes();

	}

	/**
	 * Compare tables
	 */
	public function compareTables() {

		$export = $this->getExportDatabaseArray();
		$data = $this->getExportFileArray();

	// compare table keys
		$tableDiff = array_diff_key($data, $export);
		$tableDiff = array_keys($tableDiff);

		$this->log('Table check', 'purple');

		if($tableDiff) {
			$this->log('The following tables do not existing in the current database:', 'red');

			$tableArray = [];
			foreach($tableDiff as $tableName) {
				$tableArray[] = $tableName;
			}

			$this->log(join(', ', $tablesArray), 'red');

		} else {
			$this->log('All tables match', 'green');
		}
	}

	/**
	 * Compare table fields
	 */
	public function compareTableFields() {

		$export = $this->getExportDatabaseArray();
		$data = $this->getExportFileArray();

		$this->log('Table fields check', 'purple');

		$countFieldsDiff = 0;
		foreach($data as $table => $tableInfo) {
			if(!array_key_exists($table, $export)) {
				$this->log('Table `'.$table.'` does not exist, skipping', 'yellow');

				continue;
			}

			$fields = $tableInfo['fields'];
			$exportFields = $export[$table]['fields'];

			$fieldDiff = array_diff_key($fields, $exportFields);
			$fieldDiff = array_keys($fieldDiff);

			if(!$fieldDiff) {
				continue;
			}

			$this->log('The following fields do not exist in table `'.$table.'`:', 'red');

			$fieldsArray = [];
			foreach($fieldDiff as $field) {
				$fieldsArray[] = $field;
			}

			$this->log(join(', ', $fieldsArray), 'red');

		// auto generate
			if($this->getAutoUpdate()) {

				$prevKey = null;
				foreach($fields as $field => $attributes) {
					if(!in_array($field, $fieldDiff)) {
						$prevKey = $field;

						continue;
					}

					$query = 'ALTER TABLE `'.$table.'` ADD `'.$field.'` ';

					$query .= MysqlSyntax::parseAttributes($attributes);

					if($prevKey) {
						$query .= ' AFTER `'.$prevKey.'`';
					} else {
						$query .= ' FIRST';
					}

					$this->getDatabase()->query($query);

					$prevKey = $field;
				}
			}

			$countFieldsDiff++;
		}

		if(!$countFieldsDiff) {
			$this->log('All fields for all tables match', 'green');
		}
	}

	/**
	 * Compare table attributes
	 */
	public function compareTableAttributes() {

		$export = $this->getExportDatabaseArray();
		$data = $this->getExportFileArray();

		$ignoreRowFormat = true;

		$this->log('Table attribute check', 'purple');

		$countDetailsDiff = 0;
		foreach($data as $tableName => $info) {

			$details = $info['details'];

			$exportDetails = $export[$tableName]['details'];

			if(!$ignoreRowFormat) {
				unset($details['Row_format']);
				unset($exportDetails['Row_format']);
			}

		// check diff
			$detailsDiff = array_diff_assoc($details, $exportDetails);
			if(!$detailsDiff) {
				continue;
			}

			$this->log('Details mismatch for table `'.$tableName.'`:', 'red');

			foreach($detailsDiff as $detailName => $value) {

				$this
					->log('!  - '.$detailName.':')
					->log('!'.$value, 'red')
					->log('! -> ')
					->log($exportDetails[$detailName], 'green');

				$countDetailsDiff++;
			}
		}

		if(!$countDetailsDiff) {
			$this->log('All table attributes are match', 'green');
		}
	}

	/**
	 * Compare table field attributes
	 */
	public function compareTableFieldAttributes() {

		$export = $this->getExportDatabaseArray();
		$data = $this->getExportFileArray();

		$this->log('Table field attribute check', 'purple');

		$countAttributeDiff = 0;
		foreach($data as $tableName => $tableInfo) {
			if(!array_key_exists($tableName, $export)) {
				$this->log('Table `'.$tableName.'` does not exist, skipping', 'red');

				continue;
			}

			$fields = $tableInfo['fields'];

			foreach($fields as $field => $attributes) {

			// doesn't exist
				if(!array_key_exists($field, $export[$tableName]['fields'])) {
					$this->log('Field `'.$field.'` does not exist in table `'.$tableName.'`, skipping', 'red');

					continue;
				}

				$exportAttributes = $export[$tableName]['fields'][$field];

				$attributeDiff = array_diff_assoc($attributes, $exportAttributes);
				if(!$attributeDiff) {
					continue;
				}

				$this->log('Attribute mismatch for `'.$tableName.'`.`'.$field.'`', 'red');

				foreach($attributeDiff as $attributeName => $value) {
					$this
						->log('!'.$attributeName.': ')
						->log('!'.$exportAttributes[$attributeName], 'red')
						->log('! -> ')
						->log($value, 'green');

					$countAttributeDiff++;
				}

				if($this->getAutoUpdate()) {
					$query = 'ALTER TABLE `'.$tableName.'`';
					$query .= ' MODIFY COLUMN `'.$field.'`';
					$query .= ' '.MysqlSyntax::parseAttributes($attributes);

					$this->log('Executing query: '.$query, 'cyan');

					$this->getDatabase()->query($query);
				}

				$prevKey = $field;
			}
		}

		if(!$countAttributeDiff) {
			$this->log('All table field attributes match', 'green');
		}
	}
}