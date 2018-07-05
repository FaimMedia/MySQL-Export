<?php

namespace FaimMedia\MySQLJSONExport;

use ExportException;

use Phalcon\Db\Adapter\Pdo\Mysql;

class Export {

	protected $_db;
	protected $_export = false;

	/**
	 * Set required parameters
	 */
	public function __construct(Mysql $mysql, string $folder) {

		$this->_db = $mysql;

		$this->checkFolder();

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



}