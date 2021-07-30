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
	 * @inheritDocs
	 * @param  string $fullClassName 
	 * @param  int    $readingFlags 
	 * @return \object|NULL
	 */
	public function ToInstance ($fullClassName, $readingFlags = 0) {
		if ($this->rawData === NULL)
			$this->fetchRawData(TRUE);
		if (!$this->rawData) return NULL;
		$type = new \ReflectionClass($fullClassName);
		if (!$type->hasMethod('SetValues'))
			throw new \InvalidArgumentException(
				"[".get_class()."] Class `{$fullClassName}` has no public method ".
				"`SetValues (\$data = [], \$propsFlags = 0): \MvcCore\Model`."
			);
		/** @var \MvcCore\Ext\Models\Db\Model $result */
		$result = $type->newInstanceWithoutConstructor();
		$result->SetValues($this->rawData, $readingFlags);
		return $result;
	}

	/**
	 * @inheritDocs
	 * @return array|NULL
	 */
	public function ToArray () {
		if ($this->rawData === NULL)
			$this->fetchRawData(TRUE);
		if (!$this->rawData) return NULL;
		$result = $this->rawData;
		return $result;
	}

	/**
	 * @inheritDocs
	 * @return \stdClass|NULL
	 */
	public function ToObject () {
		if ($this->rawData === NULL)
			$this->fetchRawData(TRUE);
		if (!$this->rawData) return NULL;
		$result = (object) $this->rawData;
		return $result;
	}

	/**
	 * @inheritDocs
	 * @param  string|NULL $valueColumnName 
	 * @param  string|NULL $valueType 
	 * @return bool|float|int|string|NULL
	 */
	public function ToScalar ($valueColumnName = NULL, $valueType = NULL) {
		if ($this->rawData === NULL)
			$this->fetchRawData(TRUE);
		if (
			!$this->rawData || (
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
		if ($valueType !== NULL)
			settype($itemValue, $valueType);
		return $itemValue;
	}

	/**
	 * @inheritDocs
	 * @param  callable $valueCompleter 
	 * @return mixed
	 */
	public function ToAny (callable $valueCompleter) {
		if ($this->rawData === NULL)
			$this->fetchRawData(TRUE);
		$result = $valueCompleter($this->rawData);
		return $result;
	}

	/**
	 * @inheritDocs
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
	 * @inheritDocs
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