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

interface ISingle extends \MvcCore\Ext\Models\Db\IReader {

	/**
	 * Read single fetched row into instances by given full class name and reading flags.
	 * @param  string $fullClassName
	 * @param  int    $readingFlags
	 * @throws \PDOException|\Throwable
	 * @return \object|NULL
	 */
	public function ToInstance ($fullClassName, $readingFlags = 0);

	/**
	 * Read single fetched row into associative array with keys by SQL query columns.
	 * @throws \PDOException|\Throwable
	 * @return array|NULL
	 */
	public function ToArray ();

	/**
	 * Read single fetched row into `\stdClass` object with item keys by SQL query columns.
	 * @throws \PDOException|\Throwable
	 * @return \stdClass|NULL
	 */
	public function ToObject();

	/**
	 * Read single fetched row into scalar value by first row column.
	 * @param  string|NULL $valueColumnName
	 * @param  string|NULL $valueType
	 * @throws \PDOException|\Throwable
	 * @return int|float|string|bool|NULL
	 */
	public function ToScalar ($valueColumnName = NULL, $valueType = NULL);

	/**
	 * Read single fetched row into custom item created by given callable completer called for the row.
	 * Callable has to accept one argument - raw row result.
	 * Callable completer has to return created result item instance.
	 * @param  callable $valueColumnName
	 * @param  string   $keyColumnName
	 * @param  string   $keyType
	 * @throws \PDOException|\Throwable
	 * @return mixed
	 */
	public function ToAny (callable $valueCompleter);

	/**
	 * Returns the number of loaded rows by SQL statement,
	 * it's always `0` or `1`.
	 * @throws \PDOException|\Throwable
	 * @return int
	 */
	public function GetRowsCount ();
}