<?php

namespace FaimMedia\MySQLJSONExport;

use Phalcon\Db\Adapter\Pdo as PdoAdapter;

use FaimMedia\MySQLJSONExport\Engine\Compare,
	FaimMedia\MySQLJSONExport\Engine\Export;

use FaimMedia\MySQLJSONExport\Helper\Color;

/**
 * Engine wrapper class exporter
 */
class Engine {

	protected $_db;
	protected $_folder;
	protected $_logHandler;

	protected $_export;
	protected $_compare;

	/**
	 * Set required parameters
	 */
	public function __construct(PdoAdapter $db = null, string $folder = null, $logHandler = null) {

		if($db instanceof PdoAdapter) {
			$this->setDatabase($db);
		}

		if($folder) {
			$this->setFolder($folder);
		}

		if($logHandler === null) {
			$logHandler = fopen('php://output', 'w');
		}

		$this->setLogHandler($logHandler);

		$this->_export = new Export($this);
		$this->_compare = new Compare($this);
	}

	/**
	 * Set database instance
	 */
	public function setDatabase(PdoAdapter $db): self {
		if($db instanceof PdoAdapter) {

			$this->_db = $db;

			$this->_db->connect();
		}

		return $this;
	}

	/**
	 * Get database instance
	 */
	public function getDatabase(): PdoAdapter {
		return $this->_db;
	}

	/**
	 * Set log handler
	 */
	public function setLogHandler($logHandler): self {
		if(!is_resource($logHandler)) {
			throw new Exception('Invalid resource handler');
		}

		$this->_logHandler = $logHandler;

		return $this;
	}

	/**
	 * Log message to log handler
	 */
	public function log(string $message): self {

		$newLine = true;
		if(substr($message, 0, 1) == '!') {
			$newLine = false;

			$message = substr($message, 1);
		}

		$arguments = func_get_args();
		array_shift($arguments);
		array_unshift($arguments, $message);

		$string = call_user_func_array(Color::class . '::parse', $arguments);

		if($newLine) {
			$string = trim($string).PHP_EOL;
		}

		fwrite($this->_logHandler, $string);

		return $this;
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
	 * Get export instance
	 */
	public function getExport(): Export {
		return $this->_export;
	}

	/**
	 * Get compare instance
	 */
	public function getCompare(): Compare {
		return $this->_compare;
	}

	/**
	 * Check provided folder to write the export files to
	 */
	protected function checkFolder(string $folder): string {

		if(!file_exists($folder)) {
			if(!mkdir($folder, 0775, true)) {
				throw new ExportException('Could not create export folder');
			}

			if(!file_exists($folder)) {
				throw new ExportException('The folder specified does not exist');
			}
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
	 * Export
	 */
	public function export() {
		$this->getExport()->exportAll();
	}

	/**
	 * Compare
	 */
	public function compare() {
		$this->getCompare()->compareAll();
	}

	/**
	 * Close log handler
	 */
	public function __destruct() {

		if(is_resource($this->_logHandler)) {
			fclose($this->_logHandler);
		}
	}
}