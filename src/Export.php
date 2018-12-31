<?php

namespace FaimMedia\MySQLJSONExport;

use ExportException;

use FaimMedia\MySQLJSONExport\Helper\Mysql;

class Fetch {

}
use PDO;

class Export {

	protected $_db;
	protected $_export = false;
	protected $_folder;

	/**
	 * Set required parameters
	 */
	public function __construct(Mysql $db = null, string $folder = null) {

		if($db instanceof Mysql) {
			$this->setDatabase($db);
		}

		if($folder) {
			$this->setFolder($folder);
		}
	}

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
	 * Set folder
	 */
	public function setFolder(string $folder): self {
		$this->checkFolder($folder);

		return $this;
	}

	/**
	 * Get folder
	 */
	public function getFolder(): string {
		return $this->_folder;
	}

	/**
	 * Indicates whether the database files only need to be compared or also exported
	 */
	public function setExport(bool $export = false): self {
		$this->_export = $export;

		return $this;
	}

	/**
	 * Get if exports needs to be saved
	 */
	public function getExport(): bool {
		return $this->_export;
	}

	/**
	 * Check provided folder to write the export files to
	 */
	protected function checkFolder(string $folder): string {

		if(!file_exists($folder)) {
			throw new ExportException('The folder specified does not exist');
		}

		if(!is_dir($folder)) {
			throw new ExportException('The folder specified is not a directory');
		}

		if(!is_writable($folder)) {
			throw new ExportException('The folder specified is not writeable');
		}

		$this->_folder = $folder;

		return $folder;
	}

	/**
	 * Export all tables
	 */
	public function getTablesArray(): array {

		$tablesArray = [];

	// fetch tables
		$tables = $this->getDatabase()->query('SHOW TABLES', [], PDO::FETCH_NUM);

		foreach($tables as $table) {
			$tableName = $table[0];

			$createTables = $this->getDatabase()->query('SHOW CREATE TABLE `'.$tableName.'`', [], PDO::FETCH_NUM);

			$tableArray = [
				'syntax'  => null,
				'indexes' => $this->getTableIndexesArray($tableName),
				'fields'  => $this->getTableFieldsArray($tableName),
			];

			foreach($createTables as $createTable) {
				$syntax = $createTable[1];

				$syntax = self::parseCreateTableSyntax($syntax);

				$tableArray['syntax'] = $syntax;
			}

			$tablesArray[$tableName] = $tableArray;
		}

		return $tablesArray;
	}

	/**
	 * Export all table fields
	 */
	public function getTableFieldsArray(string $tableName): array {

		$tableFieldsArray = [];

	// get tables structure
		$fields = $this->getDatabase()->query('SHOW FIELDS FROM `'.$tableName.'`');

		foreach($fields as $field) {
			$fieldKey = $field['Field'];

			unset($field['Field']);

			$tableFieldsArray[$fieldKey] = $field;
		}

		return $tableFieldsArray;
	}

	/**
	 * Export all table indexes
	 */
	public function getTableIndexesArray(string $tableName): array {

		$tableIndexesArray = [];

	// get tables indexes
		$indexes = $this->getDatabase()->query('SHOW INDEX FROM `'.$tableName.'`');

		$savedKeys = [
			'Non_unique',
			'Key_name',
			'Seq_in_index',
		];

		foreach($indexes as $index) {

			$keyName = $index['Key_name'];

			if(!array_key_exists($keyName, $tableIndexesArray)) {
				$tableIndexesArray[$keyName] = [];
			}

			$tableIndexesArray[$keyName][] = array_intersect_key($index, array_flip($savedKeys));
		}

		foreach($tableIndexesArray as $keyName => &$values) {
			usort($values, function($a, $b) {
				return $a['Seq_in_index'] - $b['Seq_in_index'];
			});
		}

		return $tableIndexesArray;
	}

	/**
	 * Export all triggers
	 */
	public function getTriggersArray(): array {

		$triggersArray = [];

	// fetch triggers
		$triggers = $this->getDatabase()->query('SHOW TRIGGERS');

		foreach($triggers as $trigger) {
			$triggerName = $trigger['Trigger'];

			$triggerArray = [
				'table'     => $trigger['Table'],
				'event'     => $trigger['Event'],
				'timing'    => $trigger['Timing'],
				'statement' => $trigger['Statement'],
				'sql_mode'  => $trigger['sql_mode'],
			];

			$createTriggers = $this->getDatabase()->query('SHOW CREATE TRIGGER `'.$triggerName.'`', [], Pdo::FETCH_NUM);

			foreach($createTriggers as $createTrigger) {
				$syntax = $createTrigger[2];

				$syntax = self::parseTriggerSyntax($syntax);
			}

			$triggersArray[$triggerName] = $triggerArray;
		}

		return $triggersArray;
	}

/**
 * STATIC
 */
	/**
	 * Parse trigger syntax
	 */
	public static function parseTriggerSyntax(string $syntax): string {
	// remove definer syntax
		$syntax = preg_replace('/\sDEFINER=`.*?`@`.*?`\s/i', ' ', $syntax);

	// replace for each row to next line
		$syntax = preg_replace('/(\sFOR\sEACH\sROW)/i', "$1", $syntax);

		return $syntax;
	}

	/**
	 * Parse create table syntax
	 */
	public static function parseCreateTableSyntax(string $syntax): string {

	// remove btree key type
		$syntax = preg_replace('/\sUSING\sBTREE/i', ' ', $syntax);

	// remove auto_increment value
		$syntax = preg_replace('/\sAUTO_INCREMENT=[0-9]+\s/i', ' ', $syntax);

	// remove row_format value
		$syntax = preg_replace('/ROW_FORMAT=(DYNAMIC|FIXED|COMPACT|COMPRESSED|DEFAULT)/', ' ', $syntax);

	// remove definer values
		$syntax = preg_replace('/\sALGORITHM=UNDEFINED\sDEFINER=`.*?`\@`.*?`\sSQL\sSECURITY\sDEFINER\s/i', ' ', $syntax);

		return $syntax;
	}
}