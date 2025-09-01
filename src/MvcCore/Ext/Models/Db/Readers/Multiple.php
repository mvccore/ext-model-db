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

class		Multiple 
extends		\MvcCore\Ext\Models\Db\Reader
implements	\MvcCore\Ext\Models\Db\Readers\IMultiple {

	/**
	 * @inheritDoc
	 * @param  string  $fullClassName 
	 * @param  int     $readingFlags 
	 * @param  ?string $keyColumnName 
	 * @param  ?string $keyType 
	 * @throws \PDOException|\Throwable
	 * @return \object[]
	 */
	public function ToInstances ($fullClassName, $readingFlags = 0, $keyColumnName = NULL, $keyType = NULL) {
		if ($this->rawData === NULL)
			$this->fetchRawData(FALSE);
		$result = [];
		$retypeKey = $keyType !== NULL;
		$useRawKey = $keyColumnName === NULL;
		$type = new \ReflectionClass($fullClassName);
		if (!$type->hasMethod('SetValues'))
			throw new \InvalidArgumentException(
				"[".get_class($this)."] Class `{$fullClassName}` has no public method ".
				"`SetValues (\$data = [], \$propsFlags = 0): \MvcCore\Model`."
			);
		$conn = $this->statement->GetConnection();
		$transcode = $conn->GetTranscode();
		foreach ($this->rawData as $rawKey => $rawItem) {
			$itemKey = $useRawKey
				? $rawKey
				: $rawItem[$keyColumnName];
			if ($retypeKey)
				settype($itemKey, $keyType);
			if ($transcode) {
				if (!$useRawKey && is_string($itemKey))
					$itemKey = $conn->TranscodeResultValue($itemKey);
				$rawValues = $conn->TranscodeResultRowValues($rawItem);
			} else {
				$rawValues = $rawItem;
			}
			/** @var \MvcCore\Ext\Models\Db\Model $item */
			$item = $type->newInstanceWithoutConstructor();
			$item->SetValues($rawValues, $readingFlags);
			$result[$itemKey] = $item;
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 * @param  ?string $keyColumnName 
	 * @param  ?string $keyType 
	 * @throws \PDOException|\Throwable
	 * @return \array[]
	 */
	public function ToArrays ($keyColumnName = NULL, $keyType = NULL) {
		if ($this->rawData === NULL)
			$this->fetchRawData(FALSE);
		$result = [];
		$retypeKey = $keyType !== NULL;
		$useRawKey = $keyColumnName === NULL;
		$conn = $this->statement->GetConnection();
		$transcode = $conn->GetTranscode();
		foreach ($this->rawData as $rawKey => $rawItem) {
			$itemKey = $useRawKey
				? $rawKey 
				: $rawItem[$keyColumnName];
			if ($retypeKey)
				settype($itemKey, $keyType);
			if ($transcode) {
				if (!$useRawKey && is_string($itemKey))
					$itemKey = $conn->TranscodeResultValue($itemKey);
				$result[$itemKey] = $conn->TranscodeResultRowValues($rawItem);
			} else {
				$result[$itemKey] = $rawItem;
			}
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 * @param  ?string $keyColumnName 
	 * @param  ?string $keyType 
	 * @throws \PDOException|\Throwable
	 * @return \stdClass[]
	 */
	public function ToObjects ($keyColumnName = NULL, $keyType = NULL) {
		if ($this->rawData === NULL)
			$this->fetchRawData(FALSE);
		$result = [];
		$retypeKey = $keyType !== NULL;
		$useRawKey = $keyColumnName === NULL;
		$conn = $this->statement->GetConnection();
		$transcode = $conn->GetTranscode();
		foreach ($this->rawData as $rawKey => $rawItem) {
			$itemKey = $useRawKey
				? $rawKey
				: $rawItem[$keyColumnName];
			if ($retypeKey)
				settype($itemKey, $keyType);
			if ($transcode) {
				if (!$useRawKey && is_string($itemKey))
					$itemKey = $conn->TranscodeResultValue($itemKey);
				$result[$itemKey] = (object) $conn->TranscodeResultRowValues($rawItem);
			} else {
				$result[$itemKey] = (object) $rawItem;
			}
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 * @param  string  $valueColumnName 
	 * @param  ?string $valueType 
	 * @param  ?string $keyColumnName 
	 * @param  ?string $keyType 
	 * @throws \PDOException|\Throwable
	 * @return \int[]|\float[]|\string[]|\bool[]|null
	 */
	public function ToScalars ($valueColumnName, $valueType = NULL, $keyColumnName = NULL, $keyType = NULL) {
		if ($this->rawData === NULL)
			$this->fetchRawData(FALSE);
		$result = [];
		$retypeKey = $keyType !== NULL;
		$retypeValue = $valueType !== NULL;
		$useRawKey = $keyColumnName === NULL;
		$conn = $this->statement->GetConnection();
		$transcode = $conn->GetTranscode();
		foreach ($this->rawData as $rawKey => $rawItem) {
			$itemKey = $useRawKey
				? $rawKey
				: $rawItem[$keyColumnName];
			if ($retypeKey)
				settype($itemKey, $keyType);
			$itemValue = array_key_exists($valueColumnName, $rawItem)
				? $rawItem[$valueColumnName]
				: NULL;
			if ($retypeValue && $itemValue !== NULL)
				settype($itemValue, $valueType);
			if ($transcode) {
				if (!$useRawKey && is_string($itemKey)) 
					$itemKey = $conn->TranscodeResultValue($itemKey);
				if (is_string($itemValue)) 
					$itemValue = $conn->TranscodeResultValue($itemValue);
			}
			$result[$itemKey] = $itemValue;
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 * @param  callable $valueColumnName Called for each result row, 1. argument is raw result item, 
	 *                                   2. argument is raw result key, 3. argument is reference for 
	 *                                   boolean `TRUE` to continue, `FALSE` to break loop. Completer 
	 *                                   has to return created result item instance.
	 * @param  ?string  $keyColumnName 
	 * @param  ?string  $keyType 
	 * @throws \PDOException|\Throwable
	 * @return array
	 */
	public function ToAny (callable $valueCompleter, $keyColumnName = NULL, $keyType = NULL) {
		if ($this->rawData === NULL)
			$this->fetchRawData(FALSE);
		$result = [];
		$retypeKey = $keyType !== NULL;
		$useRawKey = $keyColumnName === NULL;
		$conn = $this->statement->GetConnection();
		$transcode = $conn->GetTranscode();
		foreach ($this->rawData as $rawKey => $rawItem) {
			$itemKey = $useRawKey
				? $rawKey
				: $rawItem[$keyColumnName];
			if ($retypeKey)
				settype($itemKey, $keyType);
			if ($transcode) {
				if (!$useRawKey && is_string($itemKey)) 
					$itemKey = $conn->TranscodeResultValue($itemKey);
				$itemValues = $conn->TranscodeResultRowValues($rawItem);
			} else {
				$itemValues = $rawItem;
			}
			$continueOrBreak = NULL;
			$item = $valueCompleter($itemValues, $itemKey, $continueOrBreak);
			if ($continueOrBreak === NULL) {
				$result[$itemKey] = $item;
			} else if ($continueOrBreak === FALSE) {
				break;
			}
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 * @throws \PDOException|\Throwable
	 * @return int
	 */
	public function GetRowsCount () {
		if ($this->rawData === NULL)
			$this->fetchRawData(FALSE);
		if (is_array($this->rawData)) 
			return count($this->rawData);
		return 0; // In this place, `$this->rawData` is always `FALSE`
	}
	
	/**
	 * @inheritDoc
	 * @throws \PDOException|\Throwable
	 * @return array
	 */
	public function GetRawData () {
		if ($this->rawData === NULL)
			$this->fetchRawData(FALSE);
		return $this->rawData;
	}
}