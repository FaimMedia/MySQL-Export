<?php

namespace FaimMedia\MySQLJSONExport\Helper;

class Mysql {

	/**
	 * Parse array parameter to MySQL field attributes
	 */
	public static function parseFieldAttributes(array $attributes): string {
		$notNull = ($attributes['Null'] == 'NO');
		$type = $attributes['Type'];

	// set tyoe
		$query = $type;

	// check null
		if($notNull) {
			$query .= ' NOT';
		}

		$query .= ' NULL';

	// set default
		$default = $attributes['Default'];
		if($default !== null) {

			if(substr($type, 0, 3) == 'int' || substr($type, 0, 7) == 'tinyint' || substr($type, 0, 8) == 'smallint') {
				$default = (int)$default;
			}

			if(substr($type, 0, 7) == 'decimal' || substr($type, 0, 5) == 'float') {
				//$default = (float)$default;
			}

			if(is_string($default)) {
				$default = '"'.$default.'"';
			}

			$query .= ' DEFAULT '.$default;
		}

		return $query;
	}
}