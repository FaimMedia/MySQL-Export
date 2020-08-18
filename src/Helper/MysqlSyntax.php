<?php

namespace FaimMedia\MySQLJSONExport\Helper;

/**
 * MySQL Syntax helper stripper
 */
class MysqlSyntax
{

/**
 * STATIC
 */
	/**
	 * Parse trigger syntax
	 */
	public static function parseTriggerSyntax(string $syntax): string
	{
		// remove definer syntax
		$syntax = preg_replace('/\sDEFINER=`.*?`@`.*?`\s/i', ' ', $syntax);

		// replace for each row to next line
		$syntax = preg_replace('/(\sFOR\sEACH\sROW)/i', "$1", $syntax);

		return $syntax;
	}

	/**
	 * Parse create table syntax
	 */
	public static function parseCreateTableSyntax(string $syntax, $trim = true): string
	{
	// remove btree key type
		$syntax = preg_replace('/\sUSING\sBTREE/i', ' ', $syntax);

	// remove auto_increment value
		$syntax = preg_replace('/\sAUTO_INCREMENT=[0-9]+\s/i', ' ', $syntax);

	// remove row_format value
		$syntax = preg_replace('/ROW_FORMAT=(DYNAMIC|FIXED|COMPACT|COMPRESSED|DEFAULT)/', ' ', $syntax);

	// remove definer values
		$syntax = preg_replace('/\sALGORITHM=UNDEFINED\sDEFINER=`.*?`\@`.*?`\sSQL\sSECURITY\sDEFINER\s/i', ' ', $syntax);

	// trim new lines and double white spaces
		if($trim) {

		// remove new lines
			$syntax = preg_replace('/\r|\n/', ' ', $syntax);

		// remove multiple spaces
			$syntax = preg_replace('/\s\s+/', ' ', $syntax);

		}

		return $syntax;
	}

	/**
	 * Parse attributes
	 */
	public static function parseAttributes(array $attributes): string
	{
		$notNull = ($attributes['Null'] == 'NO');
		$type = strtolower($attributes['Type']);

	// set type
		$query = $type;

	// set collation
		if(isset($attributes['Collation'])) {
			$prefix = strstr($attributes['Collation'], '_', true);

			$query .= ' CHARACTER SET '.$prefix.' COLLATE '.$attributes['Collation'];
		}

	// set default
		$default = @$attributes['Default'];
		if($default !== null) {

			if(substr($type, 0, 3) == 'int' || substr($type, 0, 7) == 'tinyint' || substr($type, 0, 8) == 'smallint') {
				$default = (int)$default;
			}

			if(substr($type, 0, 7) == 'decimal' || substr($type, 0, 5) == 'float') {
				$default = (float)$default;
			}

			if(is_string($default)) {
				$default = '"'.$default.'"';
			}

			$query .= ' DEFAULT '.$default;
		}

	// check null
		if($notNull) {
			$query .= ' NOT';
		}

		$query .= ' NULL';

		if(!empty($attributes['Extra'])) {
			//$query .= ' '.strtoupper($attributes['Extra']);
		}

		return $query;
	}

	/**
	 * Generate table syntax
	 */
	protected static function generateTableSyntax(array $attributes): string
	{
		$query = '';

		if(!empty($attributes['Engine'])) {
			$query .= ' ENGINE='.$attribute['Engine'];
		}

		if(!empty($attributes['Collation'])) {
			$prefix = strstr($attributes['Collation'], '_', true);

			$query .= ' CONVERT TO CHARACTER SET '.$prefix;
			$query .= ' COLLATE '.$attributes['Collation'];
		}

		return $query;
	}

	/**
	 * Create table syntax
	 */
	public static function createTableSyntax($tableName, array $attributes): string
	{

	}

	/**
	 * Alter table syntax
	 */
	public static function alterTableSyntax($tableName, array $attributes): string
	{
		$query = 'ALTER TABLE `'.$tableName.'`';

		$query .= self::generateTableSyntax($attributes);

		return $query;
	}
}