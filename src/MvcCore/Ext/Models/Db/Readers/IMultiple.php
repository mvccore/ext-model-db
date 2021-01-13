<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Ext\Models\Db\Readers;

interface IMultiple extends \MvcCore\Ext\Models\Db\IReader {

	/**
	 * Read all fetched rows into instances by given full class name and reading flags.
	 * @param string $fullClassName
	 * @param int $readingFlags
	 * @param string $keyColumnName
	 * @param string $keyType
	 * @return \object[]
	 */
	public function ToInstances ($fullClassName, $readingFlags = 0, $keyColumnName = NULL, $keyType = NULL);

	/**
	 * Read all fetched rows into associative arrays with item keys by SQL query columns.
	 * @param string $keyColumnName
	 * @param string $keyType
	 * @return \array[]
	 */
	public function ToArrays ($keyColumnName = NULL, $keyType = NULL);

	/**
	 * Read all fetched rows into `\stdClass` objects with item keys by SQL query columns.
	 * @param string $keyColumnName
	 * @param string $keyType
	 * @return \stdClass[]
	 */
	public function ToObjects ($keyColumnName = NULL, $keyType = NULL);

	/**
	 * Read all fetched rows into scalar values by each first row column.
	 * @param string $valueColumnName
	 * @param string $valueType
	 * @param string $keyColumnName
	 * @param string $keyType
	 * @return \int[]|\float[]|\string[]|\bool[]|NULL
	 */
	public function ToScalars ($valueColumnName, $valueType = NULL, $keyColumnName = NULL, $keyType = NULL);

	/**
	 * Read all fetched rows into array with custom items created by given callable completer called for each row.
	 * Callable has to accept two arguments, first as raw row result and second is raw result key.
	 * Callable completer has to return created result item instance.
	 * @param callable $valueColumnName Called for each result row, 1. argument is raw result item, 2. argument is raw result key. Completer has to return created result item instance.
	 * @param string $keyColumnName
	 * @param string $keyType
	 * @return \mixed[]
	 */
	public function ToAny (callable $valueCompleter, $keyColumnName = NULL, $keyType = NULL);
}