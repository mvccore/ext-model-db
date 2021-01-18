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

class		Stream 
extends		\MvcCore\Ext\Models\Db\Reader
implements	\MvcCore\Ext\Models\Db\Readers\IStream {

	/**
	 * @inheritDocs
	 * @param string $fullClassName 
	 * @param int $readingFlags 
	 * @param string $keyColumnName 
	 * @param string $keyType 
	 * @return \MvcCore\Ext\Models\Db\Readers\Streams\Iterator
	 */
	public function ToInstances ($fullClassName, $readingFlags = 0, $keyColumnName = NULL, $keyType = NULL) {
		$this->iterator = new \MvcCore\Ext\Models\Db\Readers\Streams\Iterator(
			$this, \MvcCore\Ext\Models\Db\Readers\Streams\Iterator::COMPLETER_INSTANCES, 
			[$fullClassName, $readingFlags, $keyColumnName, $keyType]
		);
		return $this->iterator;
	}

	/**
	 * @inheritDocs
	 * @param string $keyColumnName 
	 * @param string $keyType 
	 * @return \MvcCore\Ext\Models\Db\Iterator
	 */
	public function ToArrays ($keyColumnName = NULL, $keyType = NULL) {
		$this->iterator = new \MvcCore\Ext\Models\Db\Readers\Streams\Iterator(
			$this, \MvcCore\Ext\Models\Db\Readers\Streams\Iterator::COMPLETER_ARRAYS, 
			[$keyColumnName, $keyType]
		);
		return $this->iterator;
	}

	/**
	 * @inheritDocs
	 * @param string $keyColumnName 
	 * @param string $keyType 
	 * @return \MvcCore\Ext\Models\Db\Readers\Streams\Iterator
	 */
	public function ToObjects ($keyColumnName = NULL, $keyType = NULL) {
		$this->iterator = new \MvcCore\Ext\Models\Db\Readers\Streams\Iterator(
			$this, \MvcCore\Ext\Models\Db\Readers\Streams\Iterator::COMPLETER_OBJECTS, 
			[$keyColumnName, $keyType]
		);
		return $this->iterator;
	}

	/**
	 * @inheritDocs
	 * @param string $valueColumnName 
	 * @param string $valueType 
	 * @param string $keyColumnName 
	 * @param string $keyType 
	 * @return \MvcCore\Ext\Models\Db\Readers\Streams\Iterator
	 */
	public function ToScalars ($valueColumnName, $valueType = NULL, $keyColumnName = NULL, $keyType = NULL) {
		$this->iterator = new \MvcCore\Ext\Models\Db\Readers\Streams\Iterator(
			$this, \MvcCore\Ext\Models\Db\Readers\Streams\Iterator::COMPLETER_SCALARS, 
			[$valueColumnName, $valueType, $keyColumnName, $keyType]
		);
		return $this->iterator;
	}

	/**
	 * @inheritDocs
	 * @param callable $valueCompleter 
	 * @param string $keyColumnName 
	 * @param string $keyType 
	 * @return \MvcCore\Ext\Models\Db\Readers\Streams\Iterator
	 */
	public function ToAny (callable $valueCompleter, $keyColumnName = NULL, $keyType = NULL) {
		$this->iterator = new \MvcCore\Ext\Models\Db\Readers\Streams\Iterator(
			$this, \MvcCore\Ext\Models\Db\Readers\Streams\Iterator::COMPLETER_ANY, 
			[$valueCompleter, $keyColumnName, $keyType]
		);
		return $this->iterator;
	}

	/**
	 * There is not possible to get all raw data for stram reader.
	 * @throws \RuntimeException
	 */
	public function GetRawData () {
		throw new \RuntimeException("There is not possible to get all raw data for stram reader.");
	}
}