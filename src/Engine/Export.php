<?php

namespace FaimMedia\MySQLJSONExport\Engine;

use Phalcon\Db;

use FaimMedia\MySQLJSONExport\Engine\AbstractEngine;

use FaimMedia\MySQLJSONExport\Helper\MysqlSyntax;

use FaimMedia\MySQLJSONExport\Exception\ExportException;

/**
 * Exports database data
 */
class Export extends AbstractEngine {

	const EXPORT_FILE_TABLES = 'Tables.json';
	const EXPORT_FILE_TRIGGERS = 'Triggers.json';

	/**
	 * Export all tables
	 */
	public function getTablesArray(): array {

		$tablesArray = [];

	// fetch tables
		$result = $this->getDatabase()->query('SHOW TABLE STATUS');
		$result->setFetchMode(Db::FETCH_ASSOC);

		$tables = $result->fetchAll($result);

		$tableFields = ['Engine', 'Version', 'Row_format', 'Collation'];

		foreach($tables as $table) {
			$tableName = array_shift($table);

			$tableArray = [
				'details' => array_intersect_key($table, array_flip($tableFields)),
			];

			$result = $this->getDatabase()->query('SHOW CREATE TABLE `'.$tableName.'`');
			$result->setFetchMode(Db::FETCH_NUM);

			$createTables = $result->fetchAll($result);

			foreach($createTables as $createTable) {
				$syntax = $createTable[1];

				$syntax = MysqlSyntax::parseCreateTableSyntax($syntax);

				$tableArray += [
					'syntax'  => $syntax,
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
	public function getTableFieldsArray(string $tableName): array {

		$tableFieldsArray = [];

	// get tables structure
		$sql = $this->getDatabase()->query('SHOW FULL COLUMNS FROM `'.$tableName.'`');
		$sql->setFetchMode(Db::FETCH_ASSOC);

		$fields = $sql->fetchAll($sql);

		foreach($fields as $field) {
			$fieldKey = $field['Field'];

			unset($field['Field']);
			unset($field['Comment']);
			unset($field['Privileges']);

			$tableFieldsArray[$fieldKey] = $field;
		}

		return $tableFieldsArray;
	}

	/**
	 * Export all triggers
	 */
	public function getTriggersArray(): array {

		$triggersArray = [];

	// fetch triggers
		$result = $this->getDatabase()->query('SHOW TRIGGERS');
		$result->setFetchMode(Db::FETCH_ASSOC);

		$triggers = $result->fetchAll($result);

		foreach($triggers as $trigger) {
			$triggerName = $trigger['Trigger'];

			$triggerArray = [
				'table'     => $trigger['Table'],
				'event'     => $trigger['Event'],
				'timing'    => $trigger['Timing'],
				'statement' => $trigger['Statement'],
				'sql_mode'  => $trigger['sql_mode'],
			];

			$result = $this->getDatabase()->query('SHOW CREATE TRIGGER `'.$triggerName.'`');
			$result->setFetchMode(Db::FETCH_NUM);

			$createTriggers = $result->fetchAll($result);

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
	public function generateTablesExport(): string {

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
	public function generateTriggersExport(): string {

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
	public function exportAll(): self {
		$this->generateTablesExport();
		$this->generateTriggersExport();

		return $this;
	}

	/**
	 * Read tables export file
	 */
	public function readTablesExportFile(): array {

		$folder = $this->getFolder();

		$exportFile = $folder.self::EXPORT_FILE_TABLES;

		$read = file_get_contents($exportFile);

		$data = json_decode($read, true);

		return $data;
	}

	/**
	 * Read triggers export file
	 */
	public function readTriggersExportFile(): array {



	}
}