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

class		Single 
extends		\MvcCore\Ext\Models\Db\Reader
implements	\MvcCore\Ext\Models\Db\Readers\ISingle {

	/**
	 * @inheritDoc
	 * @param  string $fullClassName 
	 * @param  int    $readingFlags 
	 * @throws \PDOException|\Throwable
	 * @return \object|NULL
	 */
	public function ToInstance ($fullClassName, $readingFlags = 0) {
		if ($this->rawData === NULL)
			$this->fetchRawData(TRUE);
		if ($this->rawData === FALSE) return NULL;
		$type = new \ReflectionClass($fullClassName);
		if (!$type->hasMethod('SetValues'))
			throw new \InvalidArgumentException(
				"[".get_class()."] Class `{$fullClassName}` has no public method ".
				"`SetValues (\$data = [], \$propsFlags = 0): \MvcCore\Model`."
			);
		$conn = $this->statement->GetConnection();
		$rawValues = $conn->GetTranscode()
			? $conn->TranscodeResultRowValues($this->rawData)
			: $this->rawData;
		/** @var \MvcCore\Ext\Models\Db\Model $result */
		$result = $type->newInstanceWithoutConstructor();
		$result->SetValues($rawValues, $readingFlags);
		return $result;
	}

	/**
	 * @inheritDoc
	 * @throws \PDOException|\Throwable
	 * @return array|NULL
	 */
	public function ToArray () {
		if ($this->rawData === NULL)
			$this->fetchRawData(TRUE);
		if ($this->rawData === FALSE) return NULL;
		$conn = $this->statement->GetConnection();
		$result = $conn->GetTranscode()
			? $conn->TranscodeResultRowValues($this->rawData)
			: $this->rawData;
		return $result;
	}

	/**
	 * @inheritDoc
	 * @throws \PDOException|\Throwable
	 * @return \stdClass|NULL
	 */
	public function ToObject () {
		if ($this->rawData === NULL)
			$this->fetchRawData(TRUE);
		if ($this->rawData === FALSE) return NULL;
		$conn = $this->statement->GetConnection();
		$result = $conn->GetTranscode()
			? (object) $conn->TranscodeResultRowValues($this->rawData)
			: (object) $this->rawData;
		return $result;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $valueColumnName 
	 * @param  string|NULL $valueType 
	 * @throws \PDOException|\Throwable
	 * @return bool|float|int|string|NULL
	 */
	public function ToScalar ($valueColumnName = NULL, $valueType = NULL) {
		if ($this->rawData === NULL)
			$this->fetchRawData(TRUE);
		if (
			$this->rawData === FALSE || (
				$valueColumnName !== NULL && 
				!array_key_exists($valueColumnName, $this->rawData)
			)
		) return NULL;
		if ($valueColumnName !== NULL) {
			$itemValue = $this->rawData[$valueColumnName];
		} else {
			$rawDataKeys = array_keys($this->rawData);
			$itemValue = $this->rawData[$rawDataKeys[0]];
		}
		if ($valueType !== NULL && $itemValue !== NULL)
			settype($itemValue, $valueType);
		if (is_string($itemValue)) {
			$conn = $this->statement->GetConnection();
			if ($conn->GetTranscode())
				$itemValue = $conn->TranscodeResultValue($itemValue);	
		}
		return $itemValue;
	}

	/**
	 * @inheritDoc
	 * @param  callable $valueCompleter 
	 * @throws \PDOException|\Throwable
	 * @return mixed
	 */
	public function ToAny (callable $valueCompleter) {
		if ($this->rawData === NULL)
			$this->fetchRawData(TRUE);
		if ($this->rawData === FALSE) return NULL;
		$conn = $this->statement->GetConnection();
		if ($conn->GetTranscode()) {
			$itemValues = $conn->TranscodeResultRowValues($this->rawData);
		} else {
			$itemValues = $this->rawData;
		}
		$result = $valueCompleter($itemValues);
		return $result;
	}

	/**
	 * @inheritDoc
	 * @throws \PDOException|\Throwable
	 * @return int
	 */
	public function GetRowsCount () {
		if ($this->rawData === NULL)
			$this->fetchRawData(TRUE);
		if (is_array($this->rawData)) 
			return 1;
		return 0; // In this place, `$this->rawData` is always `FALSE`
	}

	/**
	 * @inheritDoc
	 * @throws \PDOException|\Throwable
	 * @return array|NULL
	 */
	public function GetRawData () {
		if ($this->rawData === NULL)
			$this->fetchRawData(TRUE);
		return is_array($this->rawData)
			? $this->rawData
			: NULL;
	}
}