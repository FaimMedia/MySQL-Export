# FaimMedia MySQL JSON Export

Makes it easy to export, update and compare your MySQL (MariaDB) databases.

## Minimal example

```` php
	use FaimMedia\MySQLJSONExport\{
		Engine,
		Helper\Parameters,
		Helper\Mysql
	};
	
	// include autoloader
	require dirname(__DIR__, 4) . '/vendor/autoload.php';
	
	// set mysql instance
	$mysql = new Mysql([
		'host'     => '127.0.0.1',
		'dbname'   => 'database',
		'username' => 'username',
		'password' => 'password',
	
		'charset'  => 'utf8mb4',
	]);
	
	// create engine, second parameter is the folder to store the structure
	$engine = new Engine($mysql, 'cache/');

````

## Engine

### Export

Export the current database to JSON-export files.

	$engine->export();

#### Export options

By default, the export method is interactive. It will ask for, in example, if certain columns have been renamed. To disable this, set interactive to `false`.

	$engine
		->getExport()
		->setInteractive(false);

### Compare

Compare the export files with the current database and out

	$engine->compare();

#### Compare options

By default, the compare method doesn't make any changes to the database, and only outputs any differs. To auto-update the database you may use the following options:

	$engine
		->getCompare()
		->setAutoUpdate(true);

This will automatically update and rename any tables, columns and indexes. Indexes and triggers will always be deleted and recreated.

	$engine
		->getCompare()
		->setAutoDelete(true);

This will automatically remove any obsolete columns. Complete tables will never be removed, and must still be done automatically.

### Logging and output

...