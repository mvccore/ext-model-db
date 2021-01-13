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

class Multiple 
extends \MvcCore\Ext\Models\Db\Reader
implements \MvcCore\Ext\Models\Db\Readers\IMultiple
{
	/**
	 * @inheritDocs
	 * @param string $fullClassName 
	 * @param int $readingFlags 
	 * @param string $keyColumnName 
	 * @param string $keyType 
	 * @return \object[]
	 */
	public function ToInstances ($fullClassName, $readingFlags = 0, $keyColumnName = NULL, $keyType = NULL) {
		$this->fetchRawData(FALSE);
		$result = [];
		$retypeKey = $keyType !== NULL;
		$useRawKey = $keyColumnName === NULL;
		$type = new \ReflectionClass($fullClassName);
		if (!$type->hasMethod('SetValues'))
			throw new \InvalidArgumentException(
				"[".get_class()."] Class `{$fullClassName}` has no public method ".
				"`SetValues (\$data = [], \$propsFlags = 0): \MvcCore\Model`."
			);
		foreach ($this->rawData as $rawKey => $rawItem) {
			$itemKey = $useRawKey
				? $rawKey
				: $rawItem[$keyColumnName];
			if ($retypeKey)
				settype($itemKey, $keyType);
			/** @var $item \MvcCore\Ext\Models\Db\Model */
			$item = $type->newInstanceWithoutConstructor();
			$item->SetValues($rawItem, $readingFlags);
			$result[$itemKey] = $item;
		}
		$this->cleanUpData();
		return $result;
	}

	/**
	 * @inheritDocs
	 * @param string $keyColumnName 
	 * @param string $keyType 
	 * @return \array[]
	 */
	public function ToArrays ($keyColumnName = NULL, $keyType = NULL) {
		$this->fetchRawData(FALSE);
		$result = [];
		$retypeKey = $keyType !== NULL;
		$useRawKey = $keyColumnName === NULL;
		foreach ($this->rawData as $rawKey => $rawItem) {
			$itemKey = $useRawKey
				? $rawKey 
				: $rawItem[$keyColumnName];
			if ($retypeKey)
				settype($itemKey, $keyType);
			$result[$itemKey] = $rawItem;
		}
		$this->cleanUpData();
		return $result;
	}

	/**
	 * @inheritDocs
	 * @param string $keyColumnName 
	 * @param string $keyType 
	 * @return \stdClass[]
	 */
	public function ToObjects ($keyColumnName = NULL, $keyType = NULL) {
		$this->fetchRawData(FALSE);
		$result = [];
		$retypeKey = $keyType !== NULL;
		$useRawKey = $keyColumnName === NULL;
		foreach ($this->rawData as $rawKey => $rawItem) {
			$itemKey = $useRawKey
				? $rawKey
				: $rawItem[$keyColumnName];
			if ($retypeKey)
				settype($itemKey, $keyType);
			$result[$itemKey] = (object) $rawItem;
		}
		$this->cleanUpData();
		return $result;
	}

	/**
	 * @inheritDocs
	 * @param string $valueColumnName 
	 * @param string $valueType 
	 * @param string $keyColumnName 
	 * @param string $keyType 
	 * @return \int[]|\float[]|\string[]|\bool[]|NULL
	 */
	public function ToScalars ($valueColumnName, $valueType = NULL, $keyColumnName = NULL, $keyType = NULL) {
		$this->fetchRawData(FALSE);
		$result = [];
		$retypeKey = $keyType !== NULL;
		$retypeValue = $valueType !== NULL;
		$useRawKey = $keyColumnName === NULL;
		foreach ($this->rawData as $rawKey => $rawItem) {
			$itemKey = $useRawKey
				? $rawKey
				: $rawItem[$keyColumnName];
			if ($retypeKey)
				settype($itemKey, $keyType);
			$itemValue = array_key_exists($valueColumnName, $rawItem)
				? $rawItem[$valueColumnName]
				: NULL;
			if ($retypeValue)
				settype($itemValue, $valueType);
			$result[$itemKey] = $itemValue;
		}
		$this->cleanUpData();
		return $result;
	}

	/**
	 * @inheritDocs
	 * @param callable $valueColumnName Called for each result row, 1. argument is raw result item, 2. argument is raw result key. Completer has to return created result item instance.
	 * @param string $keyColumnName 
	 * @param string $keyType 
	 * @return array
	 */
	public function ToAny (callable $valueCompleter, $keyColumnName = NULL, $keyType = NULL) {
		$this->fetchRawData(FALSE);
		$result = [];
		$retypeKey = $keyType !== NULL;
		$useRawKey = $keyColumnName === NULL;
		foreach ($this->rawData as $rawKey => $rawItem) {
			$itemKey = $useRawKey
				? $rawKey
				: $rawItem[$keyColumnName];
			if ($retypeKey)
				settype($itemKey, $keyType);
			$result[$itemKey] = $valueCompleter($rawItem, $rawKey);
		}
		$this->cleanUpData();
		return $result;
	}
}