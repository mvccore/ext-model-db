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

namespace MvcCore\Ext\Models\Db\Model;

/**
 * @mixin \MvcCore\Ext\Models\Db\Model
 */
trait MagicMethods {

	/**
	 * @return void
	 */
	public function __clone () {
		list ($metaData, $sourceCodeNamesMap) = static::GetMetaData(
			static::$defaultPropsFlags, [\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_BY_CODE]
		);
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		foreach ($sourceCodeNamesMap as $propertyName => $metaDataIndex) {
			list ($propIsPrivate) = $metaData[$metaDataIndex];
			$currentValue = NULL;
			if ($propIsPrivate) {
				$prop = new \ReflectionProperty($this, $propertyName);
				$prop->setAccessible(TRUE);
				if ($phpWithTypes)
					if (!$prop->isInitialized($this))
						continue;
				$currentValue = $prop->getValue($this);
			} else if (isset($this->{$propertyName})) {
				$currentValue = $this->{$propertyName};
			}
			if (!is_object($currentValue)) continue;
			$clonedValue = clone $currentValue;
			if ($propIsPrivate) {
				$prop->setValue($this, $clonedValue);
			} else {
				$this->{$propertyName} = $clonedValue;
			}
			if (isset($this->initialValues[$propertyName]))
				$this->initialValues[$propertyName] = $clonedValue;
		}
	}

	/**
	 * @inheritDocs
	 * @param  int $propsFlags
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize ($propsFlags = 0) {
		if ($propsFlags === 0) $propsFlags = static::$defaultPropsFlags;
		$data = static::GetValues(static::$defaultPropsFlags, TRUE);
		return array_filter($data, function ($val) {
			return !is_resource($val) && !($val instanceof \Closure);
		});
	}

}