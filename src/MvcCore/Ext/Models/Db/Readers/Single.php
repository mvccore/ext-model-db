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
	 * @param string $fullClassName 
	 * @param int $readingFlags 
	 * @return \object
	 */
	public function ToInstance ($fullClassName, $readingFlags = 0) {
		$this->fetchRawData(TRUE);
		if (!$this->rawData) return NULL;
		$type = new \ReflectionClass($fullClassName);
		if (!$type->hasMethod('SetValues'))
			throw new \InvalidArgumentException(
				"[".get_class()."] Class `{$fullClassName}` has no public method ".
				"`SetValues (\$data = [], \$propsFlags = 0): \MvcCore\Model`."
			);
		/** @var $result \MvcCore\Ext\Models\Db\Model */
		$result = $type->newInstanceWithoutConstructor();
		$result->SetValues($this->rawData, $readingFlags);
		$this->cleanUpData();
		return $result;
	}

	/**
	 * @inheritDocs
	 * @return array
	 */
	public function ToArray () {
		$this->fetchRawData(TRUE);
		if (!$this->rawData) return NULL;
		$result = $this->rawData;
		$this->cleanUpData();
		return $result;
	}

	/**
	 * @inheritDocs
	 * @return \stdClass
	 */
	public function ToObject () {
		$this->fetchRawData(TRUE);
		if (!$this->rawData) return NULL;
		$result = (object) $this->rawData;
		$this->cleanUpData();
		return $result;
	}

	/**
	 * @inheritDocs
	 * @param string $valueColumnName 
	 * @param string $valueType 
	 * @return bool|float|int|null|string
	 */
	public function ToScalar ($valueColumnName, $valueType = NULL) {
		$this->fetchRawData(TRUE);
		if (
			!$this->rawData ||
			!array_key_exists($valueColumnName, $this->rawData)
		) return NULL;
		$itemValue = $this->rawData[$valueColumnName];
		if ($valueType !== NULL)
			settype($itemValue, $valueType);
		$this->cleanUpData();
		return $itemValue;
	}

	/**
	 * @inheritDocs
	 * @param callable $valueCompleter 
	 * @return mixed
	 */
	public function ToAny (callable $valueCompleter) {
		$this->fetchRawData(TRUE);
		$result = $valueCompleter($this->rawData);
		$this->cleanUpData();
		return $result;
	}
}