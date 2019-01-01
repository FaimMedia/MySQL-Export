<?php

/**
 * Export class for getting all database details and exporting them to JSON files
 */

namespace FaimMedia\MySQLJSONExport;

use FaimMedia\MySQLJSONExport\Exception\ExportException;

use FaimMedia\MySQLJSONExport\Helper\Mysql,
    FaimMedia\MySQLJSONExport\Traits\DatabaseTrait;

use PDO,
    TypeError;

class Export {
	use DatabaseTrait;

	protected $_export = false;
	protected $_folder;

	protected $_fetch;

	/**
	 * Construct
	 */
	public function __construct(Mysql $mysql) {

		$fetch = new Fetch($mysql);

		$this->setDatabase($mysql);
		$this->setFetch($fetch);
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

		if(substr($folder, -1) != '/') {
			$folder .= '/';
		}

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
	 * Export database JSON files
	 */
	public function writeJson(): array {

		try {
			$folder = $this->getFolder();
		} catch(TypeError $e) {
			throw new ExportException('Invalid folder specified');
		}

	// written files array
		$files = [];

	// write table structure
		$tablesArray = $this->getFetch()->getTablesArray();
		$tablesJson = json_encode($tablesArray, JSON_PRETTY_PRINT);

		$file = $folder . 'structure.json';

		$fopen = fopen($file, 'w');
		fwrite($fopen, $tablesJson);
		fclose($fopen);

		$files[] = $file;

	// write triggers
		$triggersArray = $this->getFetch()->getTriggersArray();
		$triggersJson = json_encode($triggersArray, JSON_PRETTY_PRINT);

		$file = $folder . 'trigger.json';

		$fopen = fopen($file, 'w');
		fwrite($fopen, $triggersJson);
		fclose($fopen);

		$files[] = $file;

		return $files;
	}

	/**
	 * Export database SQL files
	 */
	public function writeSql(): string {

	}
}