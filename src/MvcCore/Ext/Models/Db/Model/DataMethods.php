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
trait DataMethods {
	
	/**
	 * @inheritDoc
	 * @param  int  $propsFlags    All properties flags are available except flags: 
	 *                             - `\MvcCore\IModel::PROPS_INITIAL_VALUES`,
	 *                             - `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`,
	 *                             - `\MvcCore\IModel::PROPS_GET_SCALAR_VALUES`.
	 * @param  bool $getNullValues If `TRUE`, include also values with `NULL`s, 
	 *                             `FALSE` by default.
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	public function GetValues ($propsFlags = 0, $getNullValues = FALSE) {
		$getScalarValues = NULL;
		$keysByCode = NULL;
		if (($propsFlags & \MvcCore\IModel::PROPS_GET_SCALAR_VALUES) != 0) {
			$getScalarValues = TRUE;
			$propsFlags = $propsFlags ^ \MvcCore\IModel::PROPS_GET_SCALAR_VALUES;
		}
		if (($propsFlags & \MvcCore\IModel::PROPS_NAMES_BY_CODE) != 0) {
			$keysByCode = TRUE;
			$getScalarValues = $getScalarValues ?: FALSE;
			$propsFlags = $propsFlags ^ \MvcCore\IModel::PROPS_NAMES_BY_CODE;
		} else if (($propsFlags & \MvcCore\IModel::PROPS_NAMES_BY_DATABASE) != 0) {
			$keysByCode = FALSE;
			$getScalarValues = $getScalarValues ?: TRUE;
			$propsFlags = $propsFlags ^ \MvcCore\IModel::PROPS_NAMES_BY_DATABASE;
		}

		list ($metaData, $sourceCodeNamesMap) = static::GetMetaData(
			$propsFlags, [\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_BY_CODE]
		);

		$phpWithTypes = PHP_VERSION_ID >= 70400;
		$keyConversionsMethod = NULL;
		$caseSensitiveKeysMap = '';
		$stringKeyConversions = $propsFlags > 127;
		if ($stringKeyConversions) {
			$keyConversionsMethod = static::getKeyConversionMethod($propsFlags);
			$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
			if ($propsFlags > 8191)
				$caseSensitiveKeysMap = ','.implode(',', array_keys($sourceCodeNamesMap)).',';
		};

		$result = [];
		
		foreach ($sourceCodeNamesMap as $propertyName => $metaDataIndex) {
			list(
				$propIsPrivate, /*$propAllowNulls*/, /*$propTypes*/, 
				/*$propCodeName*/, $propDbColumnName, $propParsingArgs/*, $propFormatArgs,
				$propPrimaryKey, $propAutoIncrement, $propUniqueKey, $hasDefaultValue*/
			) = $metaData[$metaDataIndex];

			$propValue = NULL;
			if ($propIsPrivate) {
				$prop = new \ReflectionProperty($this, $propertyName);
				$prop->setAccessible(TRUE);
				if ($phpWithTypes) {
					if ($prop->isInitialized($this))
						$propValue = $prop->getValue($this);
				} else {
					$propValue = $prop->getValue($this);
				}
			} else if (isset($this->{$propertyName})) {
				$propValue = $this->{$propertyName};
			}

			$propValueIsNull = $propValue === NULL;
			if (!$getNullValues && $propValueIsNull)
				continue;
			
			if ($keysByCode === TRUE) {
				$resultKey = $propertyName;
			} else if ($keysByCode === FALSE) {
				if ($propDbColumnName !== NULL) {
					$resultKey = $propDbColumnName;
				} else {
					continue;
				}
			} else {
				$resultKey = $propertyName;
				if ($stringKeyConversions)
					$resultKey = static::{$keyConversionsMethod}(
						$resultKey, $toolsClass, $caseSensitiveKeysMap
					);
				
			}

			if ($getScalarValues && !$propValueIsNull)
				$propValue = static::convertToScalar(
					$propertyName, $propValue, $propParsingArgs
				);
			
			$result[$resultKey] = $propValue;
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 * @param  array $data       Raw data from database (row) or from form fields.
	 * @param  int   $propsFlags All properties flags are available.
	 * @throws \InvalidArgumentException
	 * @return \MvcCore\Ext\Models\Db\Model Current `$this` context.
	 */
	public function SetValues ($data = [], $propsFlags = 0) {
		$completeInitialValues = FALSE;
		$setOnlyDefinedProps = FALSE;
		if (($propsFlags & \MvcCore\IModel::PROPS_INITIAL_VALUES) != 0) {
			$completeInitialValues = TRUE;
			$propsFlags = $propsFlags ^ \MvcCore\IModel::PROPS_INITIAL_VALUES;
		}
		if (($propsFlags & \MvcCore\IModel::PROPS_SET_DEFINED_ONLY) != 0) {
			$setOnlyDefinedProps = TRUE;
			$propsFlags = $propsFlags ^ \MvcCore\IModel::PROPS_SET_DEFINED_ONLY;
		}
		
		list ($metaData, $sourceCodeNamesMap, $dbColumnNamesMap) = static::GetMetaData(
			$propsFlags, [
				\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_BY_CODE, 
				\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_BY_DATABASE
			]
		);
		
		$keyConversionsMethod = NULL;
		$caseSensitiveKeysMap = '';
		$stringKeyConversions = $propsFlags > 127;
		if ($stringKeyConversions) {
			$keyConversionsMethod = static::getKeyConversionMethod($propsFlags);
			$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
			if ($propsFlags > 8191)
				$caseSensitiveKeysMap = ','.implode(',', array_keys($sourceCodeNamesMap)).',';
		};
		
		foreach ($data as $dbKey => $dbValue) {
			$propertyName = $dbKey;
			$isNull = $dbValue === NULL;
			$propIsPrivate = FALSE;
			if (isset($dbColumnNamesMap[$dbKey])) {
				$metaDataIndex = $dbColumnNamesMap[$dbKey];
				list(
					$propIsPrivate, $propAllowNulls, $propTypes,
					$propCodeName, /*$propDbColumnName*/, $propParsingArgs/*, $propFormatArgs,
					$propPrimaryKey, $propAutoIncrement, $propUniqueKey, $hasDefaultValue*/
				) = $metaData[$metaDataIndex];

				if (!$propAllowNulls && $isNull) continue;

				if ($isNull) {
					$value = $dbValue;
				} else {
					$value = static::ParseToTypes(
						$dbValue, $propTypes, $propParsingArgs
					);
				}
				if ($propCodeName !== NULL) {
					$propertyName = $propCodeName;
				} else {
					if ($stringKeyConversions) 
						$propertyName = static::{$keyConversionsMethod}(
							$propertyName, $toolsClass, $caseSensitiveKeysMap
						);
				}
			} else {
				if ($stringKeyConversions) 
					$propertyName = static::{$keyConversionsMethod}(
						$propertyName, $toolsClass, $caseSensitiveKeysMap
					);
				if (isset($sourceCodeNamesMap[$propertyName])) {
					$metaDataIndex = $sourceCodeNamesMap[$propertyName];
					list(
						$propIsPrivate, $propAllowNulls, $propTypes,
						/*$propCodeName*/, /*$propDbColumnName*/, $propParsingArgs/*, $propFormatArgs,
						$propPrimaryKey, $propAutoIncrement, $propUniqueKey*/
					) = $metaData[$metaDataIndex];
					if (!$propAllowNulls && $isNull) continue;
					if ($isNull) {
						$value = $dbValue;
					} else {
						$value = static::ParseToTypes(
							$dbValue, $propTypes, $propParsingArgs
						);	
					}
				} else {
					if ($setOnlyDefinedProps) continue;
					$value = $dbValue;
				}
			}
			
			if ($propIsPrivate) {
				$prop = new \ReflectionProperty($this, $propertyName);
				$prop->setAccessible(TRUE);
				$prop->setValue($this, $value);
			} else {
				$this->{$propertyName} = $value;
			}
			
			if ($completeInitialValues)
				$this->initialValues[$propertyName] = $value;
		}
		
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  int $propsFlags All properties flags are available except flags: 
	 *                         - `\MvcCore\IModel::PROPS_INITIAL_VALUES`,
	 *                         - `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`.
	 * @throws \InvalidArgumentException
	 * @return array 
	 */
	public function GetTouched ($propsFlags = 0) {
		$getScalarValues = NULL;
		$keysByCode = NULL;
		if (($propsFlags & \MvcCore\IModel::PROPS_GET_SCALAR_VALUES) != 0) {
			$getScalarValues = TRUE;
			$propsFlags = $propsFlags ^ \MvcCore\IModel::PROPS_GET_SCALAR_VALUES;
		}
		if (($propsFlags & \MvcCore\IModel::PROPS_NAMES_BY_CODE) != 0) {
			$keysByCode = TRUE;
			$getScalarValues = $getScalarValues ?: FALSE;
			$propsFlags = $propsFlags ^ \MvcCore\IModel::PROPS_NAMES_BY_CODE;
		} else if (($propsFlags & \MvcCore\IModel::PROPS_NAMES_BY_DATABASE) != 0) {
			$keysByCode = FALSE;
			$getScalarValues = $getScalarValues ?: TRUE;
			$propsFlags = $propsFlags ^ \MvcCore\IModel::PROPS_NAMES_BY_DATABASE;
		}

		list ($metaData, $sourceCodeNamesMap) = static::GetMetaData(
			$propsFlags, [\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_BY_CODE]
		);
		
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		$keyConversionsMethod = NULL;
		$caseSensitiveKeysMap = '';
		$stringKeyConversions = $propsFlags > 127;
		if ($stringKeyConversions) {
			$keyConversionsMethod = static::getKeyConversionMethod($propsFlags);
			$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
			if ($propsFlags > 8191)
				$caseSensitiveKeysMap = ','.implode(',', array_keys($sourceCodeNamesMap)).',';
		};
		
		$result = [];
		
		foreach ($sourceCodeNamesMap as $propertyName => $metaDataIndex) {
			list(
				$propIsPrivate, /*$propAllowNulls*/, /*$propTypes*/, 
				/*$propCodeName*/, $propDbColumnName, $propParsingArgs/*, $propFormatArgs,
				$propPrimaryKey, $propAutoIncrement, $propUniqueKey, $hasDefaultValue*/
			) = $metaData[$metaDataIndex];

			$initialValue = NULL;
			$currentValue = NULL;
			if (array_key_exists($propertyName, $this->initialValues))
				$initialValue = $this->initialValues[$propertyName];

			if ($propIsPrivate) {
				$prop = new \ReflectionProperty($this, $propertyName);
				$prop->setAccessible(TRUE);
				if ($phpWithTypes) {
					if ($prop->isInitialized($this))
						$currentValue = $prop->getValue($this);
				} else {
					$currentValue = $prop->getValue($this);
				}
			} else if (isset($this->{$propertyName})) {
				$currentValue = $this->{$propertyName};
			}
			
			if (static::IsEqual($currentValue, $initialValue)) continue;
			
			if ($keysByCode === TRUE) {
				$resultKey = $propertyName;
			} else if ($keysByCode === FALSE) {
				if ($propDbColumnName !== NULL) {
					$resultKey = $propDbColumnName;
				} else {
					continue;
				}
			} else {
				$resultKey = $propertyName;
				if ($stringKeyConversions)
					$resultKey = static::{$keyConversionsMethod}(
						$resultKey, $toolsClass, $caseSensitiveKeysMap
					);
			}

			if ($getScalarValues && $currentValue !== NULL)
				$currentValue = static::convertToScalar(
					$propertyName, $currentValue, $propParsingArgs
				);
			
			$result[$resultKey] = $currentValue;
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 * @param  int $propsFlags All properties flags are available except flags: 
	 *                         - `\MvcCore\IModel::PROPS_INITIAL_VALUES`,
	 *                         - `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`,
	 *                         - `\MvcCore\IModel::PROPS_SET_DEFINED_ONLY`.
	 * @return array
	 */
	public function GetInitialValues ($propsFlags = 0) {
		$getScalarValues = NULL;
		$keysByCode = NULL;
		if (($propsFlags & \MvcCore\IModel::PROPS_GET_SCALAR_VALUES) != 0) {
			$getScalarValues = TRUE;
			$propsFlags = $propsFlags ^ \MvcCore\IModel::PROPS_GET_SCALAR_VALUES;
		}
		if (($propsFlags & \MvcCore\IModel::PROPS_NAMES_BY_CODE) != 0) {
			$keysByCode = TRUE;
			$getScalarValues = $getScalarValues ?: FALSE;
			$propsFlags = $propsFlags ^ \MvcCore\IModel::PROPS_NAMES_BY_CODE;
		} else if (($propsFlags & \MvcCore\IModel::PROPS_NAMES_BY_DATABASE) != 0) {
			$keysByCode = FALSE;
			$getScalarValues = $getScalarValues ?: TRUE;
			$propsFlags = $propsFlags ^ \MvcCore\IModel::PROPS_NAMES_BY_DATABASE;
		}

		$keyConversionsMethod = NULL;
		$caseSensitiveKeysMap = '';
		$stringKeyConversions = $propsFlags > 127;

		list ($metaData, $sourceCodeNamesMap) = static::GetMetaData(
			$propsFlags, [\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_BY_CODE]
		);

		if ($stringKeyConversions) {
			$keyConversionsMethod = static::getKeyConversionMethod($propsFlags);
			$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
			if ($propsFlags > 8191)
				$caseSensitiveKeysMap = ','.implode(',', array_keys($sourceCodeNamesMap)).',';
		};

		$result = [];

		foreach ($sourceCodeNamesMap as $propertyName => $metaDataIndex) {
			list(
				/*$propIsPrivate*/, /*$propAllowNulls*/, /*$propTypes*/, 
				/*$propCodeName*/, $propDbColumnName, $propParsingArgs/*, $propFormatArgs,
				$propPrimaryKey, $propAutoIncrement, $propUniqueKey, $hasDefaultValue*/
			) = $metaData[$metaDataIndex];

			$initialValue = NULL;
			if (array_key_exists($propertyName, $this->initialValues))
				$initialValue = $this->initialValues[$propertyName];

			if ($keysByCode === TRUE) {
				$resultKey = $propertyName;
			} else if ($keysByCode === FALSE) {
				if ($propDbColumnName !== NULL) {
					$resultKey = $propDbColumnName;
				} else {
					continue;
				}
			} else {
				$resultKey = $propertyName;
				if ($stringKeyConversions)
					$resultKey = static::{$keyConversionsMethod}(
						$resultKey, $toolsClass, $caseSensitiveKeysMap
					);
			}

			if ($getScalarValues && $initialValue !== NULL)
				$initialValue = static::convertToScalar(
					$propertyName, $initialValue, $propParsingArgs
				);
			
			$result[$resultKey] = $initialValue;
		}

		return $result;
	}

}