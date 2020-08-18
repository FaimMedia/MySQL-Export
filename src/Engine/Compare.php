<?php

namespace FaimMedia\MySQLJSONExport\Engine;

use Phalcon\Db;

use FaimMedia\MySQLJSONExport\{
	Engine\AbstractEngine,
	Helper\Mysql,
	Helper\MysqlSyntax,
	Exception\CompareException
};

/**
 * Compare database data
 */
class Compare extends AbstractEngine
{
	protected $_autoUpdate = false;
	protected $_autoDelete = false;

	protected $_exportDatabaseArray;
	protected $_exportFileArray;

	/**
	 * Set auto update
	 * If on, the engine will try to automatically correct database mismatches
	 */
	public function setAutoUpdate(bool $autoUpdate): self
	{
		$this->_autoUpdate = $autoUpdate;

		return $this;
	}

	/**
	 * Is auto update
	 */
	public function isAutoUpdate(): bool
	{
		return $this->_autoUpdate;
	}

	/**
	 * Set auto delete
	 * If on, the engine will try to automatically remove obsolete tables and fields
	 * This option isn't used by indexes and triggers, only auto-update will apply there.
	 */
	public function setAutoDelete(bool $autoDelete): self
	{
		$this->_autoDelete = $autoDelete;

		return $this;
	}

	/**
	 * Is auto delete
	 */
	public function isAutoDelete(): bool
	{
		return $this->_autoDelete;
	}

	/**
	 * Get export array from database
	 */
	public function getExportDatabaseArray(): array
	{
		if($this->_exportDatabaseArray === null) {
			$this->_exportDatabaseArray = $this->getEngine()->getExport()->getTablesArray();
		}

		return $this->_exportDatabaseArray;
	}

	/**
	 * Rename field in database export array
	 */
	public function renameExportFieldName(string $tableName, string $oldName, string $newName): bool
	{
		$this->getExportDatabaseArray();

		if(!array_key_exists($tableName, $this->_exportDatabaseArray)) {
			return false;
		}

		$fields = &$this->_exportDatabaseArray[$tableName]['fields'];

	// update export array
		$exportKeys = array_keys($fields);
		$index = array_search($oldName, $exportKeys, true);

		$exportKeys[$index] = $newName;
		$exportFields = array_combine($exportKeys, array_values($fields));

		$exportFields[$newName]['RenameFrom'] = $oldName;

		$fields = $exportFields;

		unset($fields);

		return true;
	}

	/**
	 * Get export array from export file
	 */
	public function getExportFileArray(): array
	{
		if($this->_exportFileArray === null) {
			$this->_exportFileArray = $this->getEngine()->getExport()->readTablesExportFile();
		}

		return $this->_exportFileArray;
	}

	/**
	 * Compare export file with current database connection
	 */
	public function compareAll()
	{
		$this->compareTables();
		$this->compareTableAttributes();
		$this->compareTableFields();
		$this->compareTableFieldAttributes();
		$this->compareTableIndexes();
		$this->compareTableFieldExtras();
	}

	/**
	 * Compare tables
	 */
	public function compareTables(): void
	{
		$export = $this->getExportDatabaseArray();
		$data = $this->getExportFileArray();

		// compare table keys
		$tableDiff = array_diff_key($data, $export);
		$tableDiff = array_keys($tableDiff);

		$this->log('Table check', 'purple');

		if($tableDiff) {
			$this->log('The following tables do not existing in the current database:', null, 1);

			$tablesArray = [];
			foreach($tableDiff as $tableName) {
				$tablesArray[] = $tableName;

				$this->log(" - ".$tableName, 'red', 1);
			}

			if($this->isAutoUpdate()) {

				foreach($tablesArray as $tableName) {
					$tableInfo = $data[$tableName];

					$syntax = $tableInfo['syntax'];

					$this->log('Creating missing table `'.$tableName.'`', 'cyan', 1);

					$this->db->query($syntax);
				}
			}

		} else {
			$this->log('All tables exist', 'green', 1);
		}

		// check obsolete tables
		$tableDiff = array_diff_key($export, $data);
		$tableDiff = array_keys($tableDiff);

		if($tableDiff) {
			$this->log();
			$this->log('The following tables are obsolete:', null, 1);

			$this->log(" - ".join(', ', $tableDiff), 'red', 1);

			if($this->isAutoDelete()) {
				foreach($tableDiff as $tableName) {
					$this->log(' - Removing table `'.$tableName.'`', 'red', 1);

					$this
						->db
						->query('DROP TABLE IF EXISTS `'.$tableName.'`');
				}
			}
		}
	}

	/**
	 * Compare table fields
	 */
	public function compareTableFields(): void
	{
		$export = $this->getExportDatabaseArray();
		$data = $this->getExportFileArray();

		$this->log();
		$this->log('Table fields check', 'purple');

		$countFieldsDiff = 0;
		foreach($data as $table => $tableInfo) {

			// table does not exist, skipping
			if(!array_key_exists($table, $export)) {
				continue;
			}

			$fields = $tableInfo['fields'];
			$exportFields = $export[$table]['fields'];

		// check renames
			foreach($fields as $fieldName => $field) {
				if(!isset($field['RenameFrom'])) {
					continue;
				}

				$renameFrom = $field['RenameFrom'];

				unset($field['RenameFrom']);

				if(!isset($exportFields[$renameFrom])) {
					continue;
				}

				$this->log('Field `'.$renameFrom.'` must be renamed to `'.$fieldName.'`', 'yellow', 1);

				if($this->isAutoUpdate()) {
					$this->log(' - Renaming column `'.$renameFrom.'` -> `'.$fieldName.'`', 'green', 1);
					$this->db->query('ALTER TABLE `'.$table.'` RENAME COLUMN `'.$renameFrom.'` TO `'.$fieldName.'`');
				}

			// fix checking amnd continue to next step
				unset($export[$table]['fields'][$renameFrom]);
				unset($data[$table]['fields'][$fieldName]);

				$this->renameExportFieldName($table, $renameFrom, $fieldName);

				continue 2;
			}

			$fieldDiff = array_diff_key($fields, $exportFields);
			$fieldDiff = array_keys($fieldDiff);

			if(!$fieldDiff) {
				continue;
			}

			$this->log('The following fields do not exist in table `'.$table.'`:', null, 1);

			$fieldsArray = [];
			foreach($fieldDiff as $field) {
				$fieldsArray[] = $field;
			}

			$this->log(" - ".join(', ', $fieldsArray), 'red', 1);

		// auto generate
			if($this->isAutoUpdate()) {
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

					$this->db->query($query);

					$prevKey = $field;
				}
			}

			$countFieldsDiff++;
		}

	// check obsolete fields
		$obsoleteCount = 0;

		foreach($data as $table => $tableInfo) {

			// table does not exist, skipping
			if(!array_key_exists($table, $export)) {
				continue;
			}

			$fields = $tableInfo['fields'];
			$exportFields = $export[$table]['fields'];

			// check
			$fieldDiff = array_diff_key($exportFields, $fields);
			$fieldDiff = array_keys($fieldDiff);

			if(!$fieldDiff) {
				continue;
			}

			$obsoleteCount++;

			$this->log();
			$this->log('The following fields are obsolete in table `'.$table.'`:', null, 1);

			$this->log(" - ".join(', ', $fieldDiff), 'red', 1);

			if($this->isAutoDelete()) {
				foreach($fieldDiff as $field) {
					$this->log(' - Removing field `'.$field.'`', 'red', 1);

					$this
						->db
						->query('ALTER TABLE `'.$table.'` DROP COLUMN `'.$field.'`');
				}
			}
		}

		if(!$countFieldsDiff && !$obsoleteCount) {
			$this->log('All fields for all tables match', 'green', 1);
		}

		//@todo: check sort order
	}

	/**
	 * Compare table attributes
	 */
	public function compareTableAttributes(): void
	{
		$export = $this->getExportDatabaseArray();
		$data = $this->getExportFileArray();

		$ignoreRowFormat = true;

		$this->log();
		$this->log('Table attribute check', 'purple');

		$countDetailsDiff = 0;
		foreach($data as $tableName => $info) {
			$details = $info['details'];

			// table does not exist, skipping
			if(!isset($export[$tableName])) {
				continue;
			}

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

			$this->log('Details mismatch for table `'.$tableName.'`:', 'red', 1);

			foreach($detailsDiff as $detailName => $value) {
				$this
					->log('! - '.$detailName.': ', null, 1)
					->log('!'.$exportDetails[$detailName], 'red')
					->log('! -> ')
					->log($value, 'green');

				$countDetailsDiff++;
			}

			if($this->isAutoUpdate()) {
				$query = MysqlSyntax::alterTableSyntax($tableName, $detailsDiff);

				$this
					->db
					->query($query);
			}
		}

		if(!$countDetailsDiff) {
			$this->log('All table attributes match', 'green', 1);
		}
	}

	/**
	 * Compare table field attributes
	 */
	public function compareTableFieldAttributes(): void
	{
		$export = $this->getExportDatabaseArray();
		$data = $this->getExportFileArray();

		$this->log();
		$this->log('Table field attribute check', 'purple');

		$countAttributeDiff = 0;
		foreach($data as $tableName => $tableInfo) {

			// table does not exist, skipping
			if(!array_key_exists($tableName, $export)) {
				continue;
			}

			$fields = $tableInfo['fields'];

			foreach($fields as $field => $attributes) {

				// doesn't exist
				if(!array_key_exists($field, $export[$tableName]['fields'])) {
					$this->log('Field `'.$field.'` does not exist in table `'.$tableName.'`, skipping', 'yellow', 1);
					continue;
				}

				$exportAttributes = $export[$tableName]['fields'][$field];

				foreach(['Key', 'RenameFrom'] as $value) {
					unset($attributes[$value]);
					unset($exportAttributes[$value]);
				}

				$attributeDiff = array_diff_assoc($attributes, $exportAttributes);
				if(!$attributeDiff) {
					continue;
				}

				$this->log('Attribute mismatch for `'.$tableName.'`.`'.$field.'`', 'red', 1);

				$update = true;
				foreach($attributeDiff as $attributeName => $value) {
					$this
						->log('!'.' - '.$attributeName.': ', null, 1)
						->log('!'.@$exportAttributes[$attributeName], 'red')
						->log('! -> ')
						->log($value, 'green');

					$countAttributeDiff++;
				}

				// strip INDEX properties
				$indexDiff = array_diff_key($attributeDiff, array_flip(['Key', 'Extra']));

				if(!$indexDiff) {
					//$this->log("\t".' - Not updating, contains only indexes properties', 'yellow');
					//$update = false;
				}

				if($update && $this->isAutoUpdate()) {
					$query = 'ALTER TABLE `'.$tableName.'`';
					$query .= ' MODIFY COLUMN `'.$field.'`';
					$query .= ' '.MysqlSyntax::parseAttributes($attributes);

					$this
						->db
						->query($query);
				}

				$prevKey = $field;
			}
		}

		if(!$countAttributeDiff) {
			$this->log('All table field attributes match', 'green', 1);
		}
	}

	/**
	 * Compare table indexes
	 */
	public function compareTableIndexes(): void
	{
		$export = $this->getExportDatabaseArray();
		$data = $this->getExportFileArray();

		$this->log();
		$this->log('Table indexes check', 'purple');

		$results = $this->db->query('SELECT @@FOREIGN_KEY_CHECKS', [], Mysql::FETCH_NUM);

		$isForeignKeyCheck = (bool)(int)$results[0];

		if($isForeignKeyCheck) {
			$this->db->query('SET @@FOREIGN_KEY_CHECKS = 0');
		}

		$countIndexDiff = 0;
		foreach($data as $tableName => $tableInfo) {

			// table does not exist, skipping
			if(!array_key_exists($tableName, $export)) {
				continue;
			}

			$indexes = $tableInfo['indexes'];
			$fields = $tableInfo['fields'];

			foreach($indexes as $index => $attributes) {
				$isPrimary = ($index == 'PRIMARY');

				if(!array_key_exists($index, $export[$tableName]['indexes'])) {
					$this->log('Index `'.$index.'` does not exist in table `'.$tableName.'`', null, 1);
				} else {

					$exportIndexes = $export[$tableName]['indexes'][$index];

					$attributeDiff = array_diff_assoc($attributes['fields'], $exportIndexes['fields']);
					if($exportIndexes['type'] == $attributes['type'] && !$attributeDiff) {
						continue;
					}

					$this->log('Index `'.$index.'` mismatch for table `'.$tableName.'`, removing index', 'red', 1);

					if($this->isAutoUpdate()) {
						if(!$isPrimary) {
							$this
								->db
								->query('DROP INDEX IF EXISTS `'.$index.'` ON `'.$tableName.'` LOCK = EXCLUSIVE');
						} else {
							$this
								->db
								->query('ALTER TABLE `'.$tableName.'` DROP PRIMARY KEY');
						}
					}
				}

				if($this->isAutoUpdate()) {
					if($isPrimary) {
						$query  = 'ALTER TABLE `'.$tableName.'`';
						$query .= ' ADD PRIMARY KEY(`'.join('`, `', $attributes['fields']).'`)';

					} else {
						$query = 'CREATE '.$attributes['type'].' INDEX `'.$index.'` ON `'.$tableName.'`';
						$query .= ' (`'.join('`, `', $attributes['fields']).'`)';
						$query .= ' LOCK = EXCLUSIVE';
					}

					$this->log(' - Updating index `'.$index.'`', 'green', 1);

					$this
						->db
						->query($query);
				}

				$countIndexDiff++;
			}
		}

		if($isForeignKeyCheck) {
			$this->db->query('SET @@FOREIGN_KEY_CHECKS = 1');
		}

		if(!$countIndexDiff) {
			$this->log('All table indexes match', 'green', 1);
		}
	}

	/**
	 * Compare table field extra attributes
	 */
	public function compareTableFieldExtras(): void
	{
		$export = $this->getExportDatabaseArray();
		$data = $this->getExportFileArray();

		$this->log();
		$this->log('Table field extra check', 'purple');

		$countExtraDiff = 0;
		foreach($data as $tableName => $tableInfo) {

			// table does not exist, skipping
			if(!array_key_exists($tableName, $export)) {
				continue;
			}

			$fields = $tableInfo['fields'];

			foreach($fields as $fieldName => $field) {

				if(empty($field['Extra']) || (isset($export[$tableName]['fields'][$fieldName]) && $field['Extra'] == $export[$tableName]['fields'][$fieldName]['Extra'])) {
					continue;
				}

				$this->log('Setting extra `'.$field['Extra'].'` on column `'.$fieldName.'`');

				$query  = 'ALTER TABLE `'.$tableName.'`';
				$query .= ' MODIFY COLUMN `'.$fieldName.'` '.$field['Type'];
				$query .= ' '.$field['Extra'];

				$this
					->db
					->query($query);

				$countExtraDiff++;
			}
		}

		if(!$countExtraDiff) {
			$this->log('All table extras match', 'green', 1);
		}
	}
}