<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Ext\Models\Db\Readers;

interface IStream extends \MvcCore\Ext\Models\Db\IReader {

	/**
	 * Stream and read rows one by one into instances by given full class name and reading flags.
	 * @param string $fullClassName
	 * @param int $readingFlags
	 * @param string $keyColumnName
	 * @param string $keyType
	 * @return \MvcCore\Ext\Models\Db\Readers\Streams\Iterator
	 */
	public function ToInstances ($fullClassName, $readingFlags = 0, $keyColumnName = NULL, $keyType = NULL);

	/**
	 * Stream and read rows one by one into associative arrays with item keys by SQL query columns.
	 * @param string $keyColumnName
	 * @param string $keyType
	 * @return \MvcCore\Ext\Models\Db\Readers\Streams\Iterator
	 */
	public function ToArrays ($keyColumnName = NULL, $keyType = NULL);

	/**
	 * Stream and read rows one by one into `\stdClass` objects with item keys by SQL query columns.
	 * @param string $keyColumnName
	 * @param string $keyType
	 * @return \MvcCore\Ext\Models\Db\Readers\Streams\Iterator
	 */
	public function ToObjects ($keyColumnName = NULL, $keyType = NULL);

	/**
	 * Stream and read rows one by one into scalar values by each first row column.
	 * @param string $valueColumnName
	 * @param string $valueType
	 * @param string $keyColumnName
	 * @param string $keyType
	 * @return \MvcCore\Ext\Models\Db\Readers\Streams\Iterator
	 */
	public function ToScalars ($valueColumnName, $valueType = NULL, $keyColumnName = NULL, $keyType = NULL);

	/**
	 * Stream and read rows one by one into array with custom items created by given callable completer called for each row.
	 * Callable has to accept two arguments, first as raw row result and second is raw result key.
	 * Callable completer has to return created result item instance.
	 * @param callable $valueColumnName
	 * @param string $keyColumnName
	 * @param string $keyType
	 * @return \MvcCore\Ext\Models\Db\Readers\Streams\Iterator
	 */
	public function ToAny (callable $valueCompleter, $keyColumnName = NULL, $keyType = NULL);
}