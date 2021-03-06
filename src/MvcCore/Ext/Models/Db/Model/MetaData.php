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
trait MetaData {
	
	/**
	 * @inheritDocs
	 * @param  int    $propsFlags
	 * @param  \int[] $additionalMaps
	 * @throws \RuntimeException|\InvalidArgumentException
	 * @return array
	 */
	public static function GetMetaData ($propsFlags = 0, $additionalMaps = []) {
		
		/**
		 * This is static hidden property, so it has different values 
		 * for each static call. Structure is complicated and it's 
		 * as brief as possible to serialize and userialize it very fast.
		 * @var array
		 */
		static $__metaData = [];
		
		if ($propsFlags === 0) 
			$propsFlags = static::$defaultPropsFlags;

		list (
			$cacheFlags, $accessModFlags, $inclInherit
		) = static::getMetaDataFlags($propsFlags);

		if (isset($__metaData[$cacheFlags])) {
			list ($propsMetaData, $propsAdditionalMaps) = $__metaData[$cacheFlags];
			$result = [$propsMetaData];
			foreach ($additionalMaps as $additionalMapIndex)
				$result[] = $propsAdditionalMaps[$additionalMapIndex];
			return $result;
		}
		
		$classFullName = get_called_class();
		
		$cacheClassName = static::$metaDataCacheClass;
		$cacheInstalled = class_exists($cacheClassName);
		if (!$cacheInstalled) {
			list ($propsMetaData, $propsAdditionalMaps) = static::parseMetaData(
				$classFullName, $accessModFlags, $inclInherit
			);
		} else {
			$cacheKey = implode('|', [
				static::$metaDataCacheKeyBase,
				$classFullName,
				$cacheFlags
			]);
			/** @var \MvcCore\Ext\Cache $cache */
			$cache = $cacheClassName::GetStore();
			if ($cache === NULL)
				throw new \RuntimeException("Cache has not configured default store.");
			if (!$cache->GetEnabled()) {
				list ($propsMetaData, $propsAdditionalMaps) = static::parseMetaData(
					$classFullName, $accessModFlags, $inclInherit
				);
			} else {
				list ($propsMetaData, $propsAdditionalMaps) = $cache->Load($cacheKey, function (
					$cache, $cacheKey
				) use (
					$classFullName, $accessModFlags, $inclInherit
				) {
					list ($propsMetaData, $propsAdditionalMaps) = static::parseMetaData(
						$classFullName, $accessModFlags, $inclInherit
					);
					$cache->Save(
						$cacheKey, [$propsMetaData, $propsAdditionalMaps], 
						NULL, static::$metaDataCacheTags
					);
					return [$propsMetaData, $propsAdditionalMaps];
				});
			}
		}
		
		$__metaData[$cacheFlags] = [$propsMetaData, $propsAdditionalMaps];
		
		$result = [$propsMetaData];
		foreach ($additionalMaps as $additionalMapIndex)
			$result[] = $propsAdditionalMaps[$additionalMapIndex];
		
		return $result;
	}

	/**
	 * Parse called class metadata with reflection.
	 * @param  string $classFullName 
	 * @param  int    $accessModFlags 
	 * @param  bool   $inclInherit 
	 * @throws \InvalidArgumentException 
	 * @return array
	 */
	protected static function parseMetaData ($classFullName, $accessModFlags, $inclInherit) {
		$propsByCodeKey		= \MvcCore\Ext\Models\Db\Model\IConstants::METADATA_BY_CODE;
		$propsByDbKey		= \MvcCore\Ext\Models\Db\Model\IConstants::METADATA_BY_DATABASE;
		$propsPrimaryKey	= \MvcCore\Ext\Models\Db\Model\IConstants::METADATA_PRIMARY_KEY;
		$propsUniqueKey		= \MvcCore\Ext\Models\Db\Model\IConstants::METADATA_UNIQUE_KEY;
		$propsAutoIncrement	= \MvcCore\Ext\Models\Db\Model\IConstants::METADATA_AUTO_INCREMENT;
		$classConnections	= \MvcCore\Ext\Models\Db\Model\IConstants::METADATA_CONNECTIONS;
		$classTables		= \MvcCore\Ext\Models\Db\Model\IConstants::METADATA_TABLES;
		
		$propsMetaData = [];
		$propsAdditionalMaps	= [
			$propsByCodeKey		=> [],
			$propsByDbKey		=> [],
			$propsPrimaryKey	=> [],
			$propsUniqueKey		=> [],
			$propsAutoIncrement	=> NULL,
			$classConnections	=> NULL,
			$classTables		=> NULL,
		];


		// complete properties base and extended metadata:
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		$phpWithUnionTypes = PHP_VERSION_ID >= 80000;
		$classType = new \ReflectionClass($classFullName);
		$props = $classType->getProperties($accessModFlags);
		$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
		$attributesAnotation = $toolClass::GetAttributesAnotations();
		/** @var \ReflectionProperty $prop */
		$index = 0;
		$primaryKeysIndexes = [];
		$autoIncrementMatched = FALSE;
		foreach ($props as $prop) {
			if (
				$prop->isStatic() ||
				(!$inclInherit && $prop->class !== $classFullName) ||
				isset(static::$protectedProperties[$prop->name])
			) continue;

			$resultItem = static::parseMetaDataProperty(
				$prop, [$phpWithTypes, $phpWithUnionTypes, $toolClass, $attributesAnotation, $accessModFlags]
			);
			
			$propsMetaData[$index] = $resultItem;

			list(
				/*$propIsPrivate*/, /*$propAllowNulls*/, /*$propTypes*/,
				$propCodeName, $propDbColumnName, /*$propFormatArgs*/,
				$propPrimaryKey, $propAutoIncrement, $propUniqueKey/*, $hasDefaultValue*/
			) = $resultItem;

			$propsAdditionalMaps[$propsByCodeKey][$propCodeName] = $index;

			if ($propDbColumnName !== NULL)
				$propsAdditionalMaps[$propsByDbKey][$propDbColumnName] = $index;

			if ($propPrimaryKey) {
				$propsAdditionalMaps[$propsPrimaryKey][] = $index;
				$primaryKeysIndexes[] = $index;
				if ($propAutoIncrement) {
					$autoIncrementMatched = TRUE;
					if ($propsAdditionalMaps[$propsAutoIncrement] !== NULL) {
						$propMetaDataIndex = $propsAdditionalMaps[$propsAutoIncrement];
						$propMetaData = $propsMetaData[$propMetaDataIndex];
						throw new \InvalidArgumentException(
							"[".get_class()."] Class `{$classFullName}` has defined ".
							"multiple properties with autoincrement column feature: ".
							"`{$propMetaData[3]}`, `{$prop->name}`."
						);
					}
					$propsAdditionalMaps[$propsAutoIncrement] = $index;
				}
			}
			if ($propUniqueKey) {
				$uniqueKeyProps = & $propsAdditionalMaps[$propsUniqueKey];
				if (is_bool($propUniqueKey)) {
					$uniqueKeyProps[] = $index;
				} else if (is_string($propUniqueKey)) {
					if (!isset($uniqueKeyProps[$propUniqueKey]))
						$uniqueKeyProps[$propUniqueKey] = [];
					$uniqueKeyProps[$propUniqueKey][] = $index;
				}
			}

			$index++;
		}
		// auto increment feature by dfault for single primary key integer column:
		if (!$autoIncrementMatched && count($primaryKeysIndexes) === 1) {
			$columnIndex = $primaryKeysIndexes[0];
			$propTypes = $propsMetaData[$columnIndex][2];
			if (in_array('int', $propTypes, true) || in_array('integer', $propTypes, true))
				$propsAdditionalMaps[$propsAutoIncrement] = $columnIndex;
		}

		// complete class extended metadata:
		$attrsClassesNames = [
			'connections'	=> '\\MvcCore\\Ext\\Models\\Db\\Attrs\\Connection',
			'tables'		=> '\\MvcCore\\Ext\\Models\\Db\\Attrs\\Table',
		];
		$toolsMethod = $attributesAnotation ? 'GetAttrCtorArgs' : 'GetPhpDocsTagArgs';
		$classAttrsArgs = new \stdClass;
		foreach ($attrsClassesNames as $key => $attrClassName) 
			$classAttrsArgs->{$key} = $toolClass::{$toolsMethod}(
				$classType, ($attributesAnotation
					? mb_substr($attrClassName, 1)
					: $attrClassName::PHP_DOCS_TAG_NAME)
			);
		if (isset($classAttrsArgs->connections)) 
			$propsAdditionalMaps[$classConnections] = $classAttrsArgs->connections;
		if (isset($classAttrsArgs->tables)) 
			$propsAdditionalMaps[$classTables] = $classAttrsArgs->tables;

		return [$propsMetaData, $propsAdditionalMaps];
	}

	/**
	 * Return array with property metadata:
	 * - `0`    `boolean`           `TRUE` for private property.
	 * - `1'    `boolean`           `TRUE` to allow `NULL` values.
	 * - `2`    `string[]`          Property types from code or from doc comments or empty array.
	 * - `3`    `string`            PHP code property name.
	 * - `4`    `string|NULL`	    Database column name (if defined) or `NULL`.
	 * - `5`    `mixed`             Additional convertsion data  (if defined) or `NULL`.
	 * - `6`    `bool`              `TRUE` if column is in primary key.
	 * - `7`    `bool`              `TRUE` if column has auto increment feature.
	 * - `8`    `bool|string|NULL`  `TRUE` if column is in unique key or name 
	 *                              of the unique key in database.
	 * - `9`    `bool`              `TRUE` if property has defined default value.
	 * @param  \ReflectionProperty $prop 
	 * @param  array               $params [bool $phpWithTypes, bool $phpWithUnionTypes, string $toolClass, bool $attributesAnotation]
	 * @return array
	 */
	protected static function parseMetaDataProperty (\ReflectionProperty $prop, $params) {
		list ($phpWithTypes, $phpWithUnionTypes, $toolClass, $attributesAnotation, $accessModFlags) = $params;
		// array with records under sequential indexes 0, 1, 2:
		$result = static::parseMetaDataPropertyBase($prop, $params);
		
		// source code property name to index 3:
		$result[3] = $prop->name;

		// complete extended metadata:
		$attrsClassesNames = [
			'columnName'	=> '\\MvcCore\\Ext\\Models\\Db\\Attrs\\Column',
			'columnFormat'	=> '\\MvcCore\\Ext\\Models\\Db\\Attrs\\Format',
			'keyPrimary'	=> '\\MvcCore\\Ext\\Models\\Db\\Attrs\\KeyPrimary',
			'keyUnique'		=> '\\MvcCore\\Ext\\Models\\Db\\Attrs\\KeyUnique',
		];
		$toolsMethod = $attributesAnotation ? 'GetAttrCtorArgs' : 'GetPhpDocsTagArgs';
		$propAttrs = new \stdClass;
		$propAttrsCount = 0;
		$currentProp = $prop;
		while (TRUE) {
			foreach ($attrsClassesNames as $key => $attrClassName) {
				$propAttr = $toolClass::{$toolsMethod}(
					$currentProp, ($attributesAnotation
						? mb_substr($attrClassName, 1)
						: $attrClassName::PHP_DOCS_TAG_NAME)
				);
				if ($propAttr !== NULL) {
					$propAttrs->{$key} = $propAttr;
					$propAttrsCount++;
				}
			}
			if ($propAttrsCount > 0) break;
			$propParentClass = $currentProp->getDeclaringClass()->getParentClass();
			if (!$propParentClass->hasProperty($currentProp->name)) break;
			$currentProp = $propParentClass->getProperty($currentProp->name);
		}
		
		// database column name to index 4:
		$result[4] = isset($propAttrs->columnName) && count($propAttrs->columnName) > 0
			? (isset($propAttrs->columnName[0]) ? $propAttrs->columnName[0] : $propAttrs->columnName['name'])
			: NULL;

		// column format to index 5:
		$result[5] = isset($propAttrs->columnFormat)
			? $propAttrs->columnFormat
			: NULL;

		// primary key index flag to index 6:
		$result[6] = FALSE;
		// auto increment feature flag to index 7:
		$result[7] = FALSE;
		if (isset($propAttrs->keyPrimary)) {
			$result[6] = TRUE;
			$result[7] = FALSE;
			if (is_array($propAttrs->keyPrimary) && count($propAttrs->keyPrimary) === 1) {
				$rawBool = (isset($propAttrs->keyPrimary[0]) 
					? $propAttrs->keyPrimary[0] 
					: $propAttrs->keyPrimary['autoIncrement']);
				$result[7] = is_bool($rawBool)
					? $rawBool
					: strtoupper($rawBool) === 'TRUE';
			}
		}
		
		// unique key index data to index 8:
		$result[8] = NULL;
		
		if (isset($propAttrs->keyUnique)) {
			if (is_array($propAttrs->keyUnique) && count($propAttrs->keyUnique) > 0) {
				$uKeyName = isset($propAttrs->keyUnique[0]) 
					? $propAttrs->keyUnique[0] 
					: $propAttrs->keyUnique['keyName'];
				$result[8] = $uKeyName === '' 
					? TRUE 
					: $uKeyName;
			} else {
				$result[8] = TRUE;
			}
		}

		// property has default value to index 9:
		$hasDefaultValue = FALSE;
		if ($phpWithUnionTypes) {
			$hasDefaultValue = $prop->hasDefaultValue();
		} else {
			$type = new \ReflectionClass($prop->class);
			if (!$type->isAbstract()) {
				$dummyInstance = $type->newInstanceWithoutConstructor();
				$dummyInstanceProp = new \ReflectionProperty($prop->class, $prop->name);
				$dummyInstanceProp->setAccessible(TRUE);
				$dummyValue = $dummyInstanceProp->getValue($dummyInstance);
				$hasDefaultValue = $dummyValue !== NULL;
			}
		}
		$result[9] = $hasDefaultValue;

		return $result;
	}
}