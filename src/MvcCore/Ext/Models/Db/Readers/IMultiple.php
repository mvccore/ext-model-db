<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Ext\Models\Db\Readers;

interface IMultiple extends \MvcCore\Ext\Models\Db\IReader {

	/**
	 * Read all fetched rows into instances by given full class name and reading flags.
	 * @param  string      $fullClassName
	 * @param  int	       $readingFlags
	 * @param  string|NULL $keyColumnName
	 * @param  string|NULL $keyType
	 * @throws \PDOException|\Throwable
	 * @return \object[]
	 */
	public function ToInstances ($fullClassName, $readingFlags = 0, $keyColumnName = NULL, $keyType = NULL);

	/**
	 * Read all fetched rows into associative arrays with item keys by SQL query columns.
	 * @param  string|NULL $keyColumnName
	 * @param  string|NULL $keyType
	 * @throws \PDOException|\Throwable
	 * @return \array[]
	 */
	public function ToArrays ($keyColumnName = NULL, $keyType = NULL);

	/**
	 * Read all fetched rows into `\stdClass` objects with item keys by SQL query columns.
	 * @param  string|NULL $keyColumnName
	 * @param  string|NULL $keyType
	 * @throws \PDOException|\Throwable
	 * @return \stdClass[]
	 */
	public function ToObjects ($keyColumnName = NULL, $keyType = NULL);

	/**
	 * Read all fetched rows into scalar values by each first row column.
	 * @param  string      $valueColumnName
	 * @param  string|NULL $valueType
	 * @param  string|NULL $keyColumnName
	 * @param  string|NULL $keyType
	 * @throws \PDOException|\Throwable
	 * @return \int[]|\float[]|\string[]|\bool[]|NULL
	 */
	public function ToScalars ($valueColumnName, $valueType = NULL, $keyColumnName = NULL, $keyType = NULL);

	/**
	 * Read all fetched rows into array with custom items created by given callable completer called for each row.
	 * Callable has to accept two arguments, first as raw row result, second is raw result key and third as bool
	 * reference for `TRUE` to continue and `FALSE` to break loop. Callable completer has to return created result 
	 * item instance.
	 * @param  callable    $valueColumnName Called for each result row, 1. argument is raw result item, 
	 *                                      2. argument is raw result key, 3. argument is reference for 
	 *                                      boolean `TRUE` to continue, `FALSE` to break loop. Completer 
	 *                                      has to return created result item instance.
	 * @param  string|NULL $keyColumnName
	 * @param  string|NULL $keyType
	 * @throws \PDOException|\Throwable
	 * @return \mixed[]
	 */
	public function ToAny (callable $valueCompleter, $keyColumnName = NULL, $keyType = NULL);

	/**
	 * Returns the number of loaded rows by SQL statement,
	 * it could be `0` or more.
	 * @throws \PDOException|\Throwable
	 * @return int
	 */
	public function GetRowsCount ();
}