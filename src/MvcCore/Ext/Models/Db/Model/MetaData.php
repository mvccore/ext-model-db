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

namespace MvcCore\Ext\Models\Db\Model;

trait MetaData {
	
	/**
	 * Return cached data about properties in current class to not create
	 * and parse reflection objects every time. Returned data is always array 
	 * list `list()` indexes into separate variables. First result record is
	 * always metadata array with numeric indexes, where each value is property 
	 * metadata. Second and every next result record is properties map, where
	 * keys are property names (or column names) and values are integer keys into
	 * first result record with metadata.
	 * 
	 * Every key in metadata array in first result record is integer key, 
	 * which is necessary to complete from any properties map and every value
	 * is array with metadata:
	 * - `0`	`boolean`			`TRUE` for private property.
	 * - `1'	`boolean`			`TRUE` to allow `NULL` values.
	 * - `2`	`string[]`			Property types from code or from doc comments or empty array.
	 * - `3`	`string`			PHP code property name.
	 * - `4`	`string|NULL`		Database column name (if defined) or `NULL`.
	 * - `5`	`mixed`				Additional convertsion data (if defined) or `NULL`.
	 * - `6`	`bool`				`TRUE` if column is in primary key.
	 * - `7`	`bool`				`TRUE` if column has auto increment feature.
	 * - `8`	`bool|string|NULL`	`TRUE` if column is in unique key or name 
	 *								of the unique key in database.
	 *								private properties manipulation.
	 * 
	 * Possible reading flags:
	 *  - `\MvcCore\IModel::PROPS_INHERIT`
	 *  - `\MvcCore\IModel::PROPS_PRIVATE`
	 *  - `\MvcCore\IModel::PROPS_PROTECTED`
	 *  - `\MvcCore\IModel::PROPS_PUBLIC`
	 * @param int $propsFlags
	 * @param \int[] $additionalMaps
	 * @throws \RuntimeException|\InvalidArgumentException
	 * @return array
	 */
	protected static function getMetaData ($propsFlags = 0, $additionalMaps = []) {
		/** @var $this \MvcCore\Model */
		
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
			/** @var $cache \MvcCore\Ext\Cache */
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
	 * @param string $classFullName 
	 * @param int $accessModFlags 
	 * @param bool $inclInherit 
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

		$phpWithTypes = PHP_VERSION_ID >= 70400;
		$classType = new \ReflectionClass($classFullName);
		$props = $classType->getProperties($accessModFlags);
		/** @var $prop \ReflectionProperty */
		$index = 0;
		foreach ($props as $prop) {
			if (
				$prop->isStatic() ||
				(!$inclInherit && $prop->class !== $classFullName) ||
				isset(static::$protectedProperties[$prop->name])
			) continue;

			$resultItem = static::parseMetaDataProperty($prop, $phpWithTypes);
			
			$propsMetaData[$index] = $resultItem;

			list(
				/*$propIsPrivate*/, /*$propAllowNulls*/, /*$propTypes*/,
				$propCodeName, $propDbColumnName, /*$propFormatArgs*/,
				$propPrimaryKey, $propAutoIncrement, $propUniqueKey
			) = $resultItem;

			$propsAdditionalMaps[$propsByCodeKey][$propCodeName] = $index;

			if ($propDbColumnName !== NULL)
				$propsAdditionalMaps[$propsByDbKey][$propDbColumnName] = $index;

			if ($propPrimaryKey) {
				$propsAdditionalMaps[$propsPrimaryKey][] = $index;
				if ($propAutoIncrement) {
					if ($propsAdditionalMaps[$propsAutoIncrement] !== NULL) {
						$propMetaDataIndex = $propsAdditionalMaps[$propsAutoIncrement];
						$propMetaData = $propsMetaData[$propMetaDataIndex];
						throw new \InvalidArgumentException(
							"[".get_class()."] Class `{$classFullName}` has defined ".
							"multiple properties with autoincrement column feature: ".
							"`{$propMetaData[4]}`, `{$prop->name}`."
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

		$classAttrsArgs = \MvcCore\Ext\Models\Db\Misc\Reflection::GetClassAttrsArgs(
			$classType
		);
		if (isset($classAttrsArgs->connections)) 
			$propsAdditionalMaps[$classConnections] = $classAttrsArgs->connections;
		if (isset($classAttrsArgs->tables)) 
			$propsAdditionalMaps[$classTables] = $classAttrsArgs->tables;

		return [$propsMetaData, $propsAdditionalMaps];
	}

	/**
	 * Return array with property metadata:
	 * - `0`	`boolean`			`TRUE` for private property.
	 * - `1'	`boolean`			`TRUE` to allow `NULL` values.
	 * - `2`	`string[]`			Property types from code or from doc comments or empty array.
	 * - `3`	`string`			PHP code property name.
	 * - `4`	`string|NULL`		Database column name (if defined) or `NULL`.
	 * - `5`	`mixed`				Additional convertsion data  (if defined) or `NULL`.
	 * - `6`	`bool`				`TRUE` if column is in primary key.
	 * - `7`	`bool`				`TRUE` if column has auto increment feature.
	 * - `8`	`bool|string|NULL`	`TRUE` if column is in unique key or name 
	 *								of the unique key in database.
	 * @param \ReflectionProperty $prop 
	 * @param bool $phpWithTypes 
	 * @return array
	 */
	protected static function parseMetaDataProperty (\ReflectionProperty $prop, $phpWithTypes) {
		// array with records under sequential indexes 0, 1, 2:
		$result = static::parseMetaDataPropertyBase($prop, $phpWithTypes);
		
		// source code property name to index 3:
		$result[3] = $prop->name;

		$propAttrs = \MvcCore\Ext\Models\Db\Misc\Reflection::GetClassPropertyAttrsArgs($prop);
		
		// database column name to index 4:
		$result[4] = isset($propAttrs->columnName) && count($propAttrs->columnName) > 0
			? $propAttrs->columnName[0]
			: NULL;

		// column format to index 5:
		$result[5] = isset($propAttrs->columnFormat)
			? $propAttrs->columnFormat
			: NULL;

		// primary key index flag to index 6:
		$result[6] = FALSE;
		// auto increment feature flag to index 7:
		$result[7] = FALSE;
		if ($propAttrs->keyPrimary !== NULL) {
			$result[6] = TRUE;
			$result[7] = TRUE; // auto increment feature always by default
			if (is_array($propAttrs->keyPrimary) && count($propAttrs->keyPrimary) > 0) {
				$rawBool = $propAttrs->keyPrimary[0];
				$result[7] = is_bool($rawBool)
					? $rawBool
					: strtoupper($rawBool) === 'TRUE';
			}
		}
		
		// unique key index data to index 8:
		$result[8] = NULL;
		
		if ($propAttrs->keyUnique !== NULL) 
			$result[8] = is_array($propAttrs->keyUnique) && count($propAttrs->keyUnique) > 0
				? ($propAttrs->keyUnique[0] === '' ? TRUE : $propAttrs->keyUnique[0])
				: TRUE;

		return $result;
	}
}