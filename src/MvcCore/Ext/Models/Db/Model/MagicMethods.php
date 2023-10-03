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
	 * @inheritDoc
	 * @param  int $propsFlags
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize ($propsFlags = 0) {
		if ($propsFlags === 0) $propsFlags = static::$defaultPropsFlags;
		$data = static::GetValues($propsFlags, TRUE);
		return array_filter($data, function ($val) {
			return !is_resource($val) && !($val instanceof \Closure);
		});
	}

	/**
	 * @return void
	 */
	public function __clone () {
		$this->__cloneBase();
		// set NULL value into auto increment property or into primary/unique key(s) property/properties
		$metaDataCollections = static::GetMetaData(
			static::$defaultPropsFlags, [
				\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_AUTO_INCREMENT,
				\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_PRIMARY_KEY,
				\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_UNIQUE_KEY
			]
		);
		list (
			$metaData, $autoIncrIndex, $primaryKeyColumnsIndexes, $uniqueKeyColumnsIndexes
		) = $metaDataCollections;
		$propsIndexesToSetNull = [];
		$hasAutoIncrColumn = isset($metaData[$autoIncrIndex]);
		if ($hasAutoIncrColumn) {
			$propsIndexesToSetNull = [$autoIncrIndex];
		} else if (count($primaryKeyColumnsIndexes) > 0) {
			$propsIndexesToSetNull = $primaryKeyColumnsIndexes;
		} else if (count($uniqueKeyColumnsIndexes) > 0) {
			$propsIndexesToSetNull = static::parseMetaDataGetPrimaryUniqueKeys($uniqueKeyColumnsIndexes);
		}
		foreach ($propsIndexesToSetNull as $propIndexToSetNull) {
			list(
				$autoIncrPropIsPrivate, /*$autoIncrPropAllowNulls*/, 
				/*$autoIncrPropTypes*/, $autoIncrPropCodeName
			) = $metaData[$propIndexToSetNull];
			if ($autoIncrPropIsPrivate) {
				$prop = new \ReflectionProperty($this, $autoIncrPropCodeName);
				$prop->setAccessible(TRUE);
				$prop->setValue($this, NULL);
			} else {
				$this->{$autoIncrPropCodeName} = NULL;
			}
			if (isset($this->initialValues[$autoIncrPropCodeName]))
				unset($this->initialValues[$autoIncrPropCodeName]);
		}
	}
}