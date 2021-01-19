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
	 * @param string $fullClassName
	 * @param int $readingFlags
	 * @return \object
	 */
	public function ToInstance ($fullClassName, $readingFlags = 0);

	/**
	 * Read single fetched row into associative array with keys by SQL query columns.
	 * @return array
	 */
	public function ToArray ();

	/**
	 * Read single fetched row into `\stdClass` object with item keys by SQL query columns.
	 * @return \stdClass
	 */
	public function ToObject();

	/**
	 * Read single fetched row into scalar value by first row column.
	 * @param string $valueColumnName
	 * @param string $valueType
	 * @return int|float|string|bool|NULL
	 */
	public function ToScalar ($valueColumnName, $valueType = NULL);

	/**
	 * Read single fetched row into custom item created by given callable completer called for the row.
	 * Callable has to accept one argument - raw row result.
	 * Callable completer has to return created result item instance.
	 * @param callable $valueColumnName
	 * @param string $keyColumnName
	 * @param string $keyType
	 * @return mixed
	 */
	public function ToAny (callable $valueCompleter);

	/**
	 * Returns the number of loaded rows by SQL statement,
	 * it's always `0` or `1`.
	 * @return int
	 */
	public function GetRowsCount ();
}