<?php

namespace FaimMedia\MySQLJSONExport\Engine;

use FaimMedia\MySQLJSONExport\{
	Engine\AbstractEngine,
	Helper\Mysql,
	Helper\MysqlSyntax,
	Exception\ExportException
};

/**
 * Exports database data
 */
class Export extends AbstractEngine
{
	const EXPORT_FILE_TABLES = 'Tables.json';
	const EXPORT_FILE_TRIGGERS = 'Triggers.json';

	/**
	 * Export all tables
	 */
	public function getTablesArray(): array
	{
		$tablesArray = [];

	// fetch tables
		$tables = $this
			->db
			->query('SHOW TABLE STATUS');

		$tableFields = ['Engine', 'Version', 'Row_format', 'Collation'];

		foreach($tables as $table) {
			$tableName = array_shift($table);

			$tableArray = [
				'details' => array_intersect_key($table, array_flip($tableFields)),
			];

			$createTables = $this
				->db
				->query('SHOW CREATE TABLE `'.$tableName.'`', [], Mysql::FETCH_NUM);

			foreach($createTables as $createTable) {
				$syntax = $createTable[1];

				$syntax = MysqlSyntax::parseCreateTableSyntax($syntax);

				$tableArray += [
					'syntax'  => $syntax,
					'indexes' => $this->getTableIndexesArray($tableName),
					'fields'  => $this->getTableFieldsArray($tableName),
				];
			}

			$tablesArray[$tableName] = $tableArray;
		}

		return $tablesArray;
	}

	/**
	 * Export all table fields
	 */
	public function getTableFieldsArray(string $tableName): array
	{
		$tableFieldsArray = [];

	// get tables structure
		$fields = $this
			->db
			->query('SHOW FULL COLUMNS FROM `'.$tableName.'`');

		foreach($fields as $field) {
			$fieldKey = $field['Field'];

			$field['Type'] = strtoupper($field['Type']);

			unset($field['Field']);
			unset($field['Comment']);
			unset($field['Privileges']);

			$tableFieldsArray[$fieldKey] = $field;
		}

		return $tableFieldsArray;
	}

	/**
	 * Export table indexes
	 */
	public function getTableIndexesArray(string $tableName): array
	{
		$tableIndexArray = [];

		// get table indexes
		$rows = $this
			->db
			->query('SHOW INDEXES FROM `'.$tableName.'`');

		foreach($rows as $row) {
			$keyName = $row['Key_name'];
			$sequence = (int)$row['Seq_in_index'];

			$type = 'INDEX';
			if($keyName === 'PRIMARY') {
				$type = 'PRIMARY';
			} else if($row['Index_type'] == 'FULLTEXT') {
				$type = 'FULLTEXT';
			} else if((int)$row['Non_unique'] === 0) {
				$type = 'UNIQUE';
			}

			if(!array_key_exists($keyName, $tableIndexArray)) {
				$tableIndexArray[$keyName] = [
					'type'      => $type,
					'fields'    => [],
				];
			}

			$tableIndexArray[$keyName]['fields'][$sequence] = $row['Column_name'];
		}

		return $tableIndexArray;
	}

	/**
	 * Export all triggers
	 */
	public function getTriggersArray(): array
	{
		$triggersArray = [];

	// fetch triggers
		$triggers = $this
			->db
			->query('SHOW TRIGGERS');

		foreach($triggers as $trigger) {
			$triggerName = $trigger['Trigger'];

			$triggerArray = [
				'table'     => $trigger['Table'],
				'event'     => $trigger['Event'],
				'timing'    => $trigger['Timing'],
				'statement' => $trigger['Statement'],
				'sql_mode'  => $trigger['sql_mode'],
			];

			$createTriggers = $this
				->db
				->query('SHOW CREATE TRIGGER `'.$triggerName.'`', [], Mysql::FETCH_NU);

			foreach($createTriggers as $createTrigger) {
				$syntax = $createTrigger[2];

				$syntax = MysqlSyntax::parseTriggerSyntax($syntax);
			}

			$triggersArray[$triggerName] = $triggerArray;
		}

		return $triggersArray;
	}

	/**
	 * Generate table export
	 */
	public function generateTablesExport(): string
	{
		$folder = $this->getFolder();

		$json = json_encode($this->getTablesArray(), JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES);

		$file = $folder.self::EXPORT_FILE_TABLES;

		$fopen = fopen($file, 'w+');
		fwrite($fopen, $json);
		fclose($fopen);

		return $file;
	}

	/**
	 * Generate trigger export
	 */
	public function generateTriggersExport(): string
	{
		$folder = $this->getFolder();

		$json = json_encode($this->getTriggersArray(), JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES);

		$file = $folder.self::EXPORT_FILE_TRIGGERS;

		$fopen = fopen($file, 'w+');
		fwrite($fopen, $json);
		fclose($fopen);

		return $file;
	}

	/**
	 * Export all
	 */
	public function exportAll(): self
	{
		$this->generateTablesExport();
		$this->generateTriggersExport();

		return $this;
	}

	/**
	 * Read tables export file
	 */
	public function readTablesExportFile(): array
	{
		$folder = $this->getFolder();

		clearstatcache();

		$exportFile = $folder.self::EXPORT_FILE_TABLES;

		$read = file_get_contents($exportFile);

		$data = json_decode($read, true);

		if(json_last_error() !== JSON_ERROR_NONE) {
			throw new ExportException('Could not read JSON-file: '.json_last_error_msg());
		}

		return $data;
	}

	/**
	 * Read triggers export file
	 */
	public function readTriggersExportFile(): array
	{



	}
}