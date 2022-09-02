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
trait EditMethods {
	
	/**
	 * @inheritDocs
	 * @param  bool|NULL $createNew
	 * @param  int       $propsFlags
	 * @throws \InvalidArgumentException|\PDOException|\Throwable
	 * @return bool
	 */
	public function Save ($createNew = NULL, $propsFlags = 0) {
		/** @var \MvcCore\Ext\Models\Db\Model $this */
		return static::editSave(
			$this, $createNew, $propsFlags, static::getEditMetaDataCollections($propsFlags)
		);
	}

	/**
	 * @inheritDocs
	 * @param  int $propsFlags
	 * @throws \InvalidArgumentException 
	 * @return bool
	 */
	public function IsNew ($propsFlags = 0) {
		/** @var \MvcCore\Ext\Models\Db\Model $this */
		return static::editIsNew(
			$this, $propsFlags, static::getEditMetaDataCollections($propsFlags)
		);
	}

	/**
	 * @inheritDocs
	 * @param  int $propsFlags
	 * @throws \InvalidArgumentException|\PDOException|\Throwable
	 * @return bool
	 */
	public function Insert ($propsFlags = 0) {
		/** @var \MvcCore\Ext\Models\Db\Model $this */
		return static::editInsert(
			$this, $propsFlags, static::getEditMetaDataCollections($propsFlags)
		);
	}

	/**
	 * @inheritDocs
	 * @param  int $propsFlags
	 * @throws \InvalidArgumentException|\PDOException|\Throwable
	 * @return bool
	 */
	public function Update ($propsFlags = 0) {
		/** @var \MvcCore\Ext\Models\Db\Model $this */
		return static::editUpdate(
			$this, $propsFlags, static::getEditMetaDataCollections($propsFlags)
		);
	}

	/**
	 * @inheritDocs
	 * @param  int $propsFlags 
	 * @throws \InvalidArgumentException|\PDOException|\Throwable
	 * @return bool
	 */
	public function Delete ($propsFlags = 0) {
		/** @var \MvcCore\Ext\Models\Db\Model $this */
		return static::editDelete(
			$this, $propsFlags, static::getEditMetaDataCollections($propsFlags)
		);
	}
	
	/**
	 * @inheritDocs
	 * @param  bool $autoCreate
	 * @return \MvcCore\Ext\Models\Db\Resources\Edit|NULL
	 */
	public function GetEditResource ($autoCreate = TRUE) {
		if ($autoCreate && $this->editResource === NULL) {
			$providerEditResourceClass = static::$providerEditResourceClass;
			$this->editResource = new $providerEditResourceClass;
		}
		return $this->editResource;
	}

	/**
	 * @inheritDocs
	 * @param  \MvcCore\Ext\Models\Db\Resources\Edit|NULL
	 * @return \MvcCore\Ext\Models\Db\Model
	 */
	public function SetEditResource (\MvcCore\Ext\Models\Db\Resources\IEdit $editResource = NULL) {
		/** @var \MvcCore\Ext\Models\Db\Model $this */
		$this->editResource = $editResource;
		return $this;
	}


	/**
	 * Process instance database SQL INSERT or UPDATE by automaticly founded key data.
	 * Return `TRUE` if there is inserted or updated 1 or more rows or return 
	 * `FALSE` if there is no row inserted or updated. Thrown an exception in any database error.
	 * @param  \MvcCore\Ext\Models\Db\Model $context 
	 * @param  bool|NULL                    $createNew 
	 * @param  int                          $propsFlags 
	 * @param  array                        $metaDataCollections 
	 * @throws \InvalidArgumentException|\PDOException|\Throwable
	 * @return bool
	 */
	protected static function editSave ($context, $createNew, $propsFlags, $metaDataCollections) {
		if ($createNew === NULL)
			$createNew = static::editIsNew($context, $propsFlags, $metaDataCollections);
		if ($createNew) {
			return static::editInsert($context, $propsFlags, $metaDataCollections);
		} else {
			return static::editUpdate($context, $propsFlags, $metaDataCollections);
		}
	}

	/**
	 * Try to determinate, if model instance is new or already existing in 
	 * database by property with anotated auto increment column.
	 * If property is not initialized or if it has `NULL` value, than 
	 * model instance is recognized as new and `TRUE` is returned, `FALSE` otherwise.
	 * @param  \MvcCore\Ext\Models\Db\Model $context 
	 * @param  int                          $propsFlags 
	 * @param  array                        $metaDataCollections 
	 * @throws \InvalidArgumentException
	 * @return bool
	 */
	protected static function editIsNew ($context, $propsFlags, $metaDataCollections) {
		list(
			$metaData, $autoIncrementIndex
		) = $metaDataCollections;
		if ($autoIncrementIndex === NULL) {
			throw new \InvalidArgumentException(
				"[".get_class()."] There was not possible to recognize ".
				"if database model instance is new or already existing ".
				"in database. Please define property with primary key ".
				"column attribute anotation."
			);
		} else {
			list(
				$propIsPrivate, /*$propAllowNulls*/, /*$propTypes*/, 
				$propCodeName/*, $propDbColumnName, $propParserArgs, $propFormatArgs,
				$propPrimaryKey, $propAutoIncrement, $propUniqueKey*/
			) = $metaData[$autoIncrementIndex];
			if ($propIsPrivate) {
				$prop = new \ReflectionProperty($context, $propCodeName);
				$prop->setAccessible(TRUE);
				if (PHP_VERSION_ID >= 70400) {
					return !(
						$prop->isInitialized($context) && 
						$prop->getValue($context) !== NULL
					);
				} else {
					return $prop->getValue($context) === NULL;
				}
			} else {
				return !isset($context->{$propCodeName});
			}
		}
	}

	/**
	 * Process instance database SQL INSERT by automaticly founded key data.
	 * Return `TRUE` if there is updated 1 or more rows or return 
	 * `FALSE` if there is no row updated. Thrown an exception in any database error.
	 * @param  \MvcCore\Ext\Models\Db\Model $context 
	 * @param  int                          $propsFlags 
	 * @param  array                        $metaDataCollections 
	 * @throws \PDOException|\Throwable
	 * @return bool
	 */
	protected static function editInsert ($context, $propsFlags, $metaDataCollections) {
		list (
			$metaData, $autoIncrIndex, 
			/*$primaryKeyColumnsIndexes*/, /*$uniqueKeyColumnsIndexes*/, 
			$connectionArgs, $tableArgs, $columnsDbNamesMap
		) = $metaDataCollections;
		
		$initValuesFlag = \MvcCore\IModel::PROPS_INITIAL_VALUES;
		$hasInitValuesFlag = ($propsFlags & $initValuesFlag) != 0;
		if ($hasInitValuesFlag) // remove init values flag
			$propsFlags = ~((~$propsFlags) | $initValuesFlag);
		if ($propsFlags === 0) 
			$propsFlags = static::$defaultPropsFlags;
		$allValues = $context->GetValues(
			$propsFlags | \MvcCore\IModel::PROPS_NAMES_BY_DATABASE
		);

		$hasAutoIncrColumn = isset($metaData[$autoIncrIndex]);
		if (!$hasAutoIncrColumn) {
			$autoIncrPropDbName = NULL;
		} else {
			list(
				$autoIncrPropIsPrivate, /*$autoIncrPropAllowNulls*/, $autoIncrPropTypes, 
				$autoIncrPropCodeName, $autoIncrPropDbName/*, $autoIncrPropParserArgs, $autoIncrPropFormatArgs,
				$autoIncrPropPrimaryKey, $autoIncrPropAutoIncrement, $autoIncrPropUniqueKey*/
			) = $metaData[$autoIncrIndex];
			if (isset($allValues[$autoIncrPropDbName]))
				unset($allValues[$autoIncrPropDbName]);
		}
		
		$error = NULL;
		if (count($allValues) === 0) {
			// no data to insert
			list ($success, $affectedRows) = [FALSE, 0];
		} else {
			$editResource = $context->GetEditResource();
			$connectionNameOrIndex = isset($connectionArgs[0]) ? $connectionArgs[0] : NULL;
			list (
				$success, $affectedRows, $rawNewId, $error
			) = $editResource->Insert(
				$connectionNameOrIndex, $tableArgs[0], $allValues, get_class($context), $autoIncrPropDbName
			);
		}

		if ($success && $affectedRows > 0) {
			if ($hasAutoIncrColumn) {
				$newId = static::parseToTypes($rawNewId, $autoIncrPropTypes);
				if ($autoIncrPropIsPrivate) {
					$prop = new \ReflectionProperty($context, $autoIncrPropCodeName);
					$prop->setAccessible(TRUE);
					$prop->setValue($context, $newId);
				} else {
					$context->{$autoIncrPropCodeName} = $newId;
				}
				if ($hasInitValuesFlag)
					$context->initialValues[$autoIncrPropCodeName] = $newId;
			}
			if ($hasInitValuesFlag) {
				foreach (array_keys($allValues) as $dbColumnName) {
					if (!isset($columnsDbNamesMap[$dbColumnName])) continue;
					$metaDataIndex = $columnsDbNamesMap[$dbColumnName];
					if ($metaDataIndex === $autoIncrIndex) continue;
					list(
						$propIsPrivate, /*$propAllowNulls*/, /*$propTypes*/, 
						$propCodeName, /*$propDbColumnName, $propParserArgs, $propFormatArgs,
						$propPrimaryKey, $propAutoIncrement, $propUniqueKey*/
					) = $metaData[$metaDataIndex];
					if ($propIsPrivate) {
						$prop = new \ReflectionProperty($context, $propCodeName);
						$prop->setAccessible(TRUE);
						$context->initialValues[$propCodeName] = $prop->getValue($context);
					} else {
						$context->initialValues[$propCodeName] = $context->{$propCodeName};
					}
				}
			}
			return TRUE;
		} else if ($error !== NULL) {
			throw $error;
		} else {
			return FALSE;
		}
	}

	/**
	 * Process instance database SQL UPDATE by automaticly founded key data.
	 * Return `TRUE` if there is updated 1 or more rows or return 
	 * `FALSE` if there is no row updated. Thrown an exception in any database error.
	 * @param  \MvcCore\Ext\Models\Db\Model $context 
	 * @param  int                          $propsFlags 
	 * @param  array                        $metaDataCollections 
	 * @throws \InvalidArgumentException|\PDOException|\Throwable
	 * @return bool
	 */
	protected static function editUpdate ($context, $propsFlags, $metaDataCollections) {
		list (
			$metaData, $autoIncrIndex, 
			$primaryKeyColumnsIndexes, $uniqueKeyColumnsIndexes, 
			$connectionArgs, $tableArgs, $columnsDbNamesMap
		) = $metaDataCollections;
		
		$initValuesFlag = \MvcCore\IModel::PROPS_INITIAL_VALUES;
		$hasInitValuesFlag = ($propsFlags & $initValuesFlag) != 0;
		if ($hasInitValuesFlag) // remove init values flag
			$propsFlags = ~((~$propsFlags) | $initValuesFlag);
		if ($propsFlags === 0) 
			$propsFlags = static::$defaultPropsFlags;
		$touchedValues = $context->GetTouched(
			$propsFlags | \MvcCore\IModel::PROPS_NAMES_BY_DATABASE
		);

		$keysColumns = static::getEditAllKeysData(
			$context, $metaData, $primaryKeyColumnsIndexes, $uniqueKeyColumnsIndexes
		);
		$dataColumns = array_diff_assoc($touchedValues, $keysColumns);
	
		if (count($dataColumns) === 0) {
			// no data to update
			list ($success, $affectedRows) = [FALSE, 0];
		} else {
			$editResource = $context->GetEditResource();
			$connectionNameOrIndex = isset($connectionArgs[0]) ? $connectionArgs[0] : NULL;
			list (
				$success, $affectedRows
			) = $editResource->Update(
				$connectionNameOrIndex, $tableArgs[0], $keysColumns, $dataColumns
			);
		}
		
		$result = $success && $affectedRows > 0;

		if ($result && $hasInitValuesFlag) {
			foreach (array_keys($touchedValues) as $dbColumnName) {
				if (!isset($columnsDbNamesMap[$dbColumnName])) continue;
				$metaDataIndex = $columnsDbNamesMap[$dbColumnName];
				if ($metaDataIndex === $autoIncrIndex) continue;
				list(
					$propIsPrivate, /*$propAllowNulls*/, /*$propTypes*/, 
					$propCodeName, /*$propDbColumnName, $propParserArgs, $propFormatArgs,
					$propPrimaryKey, $propAutoIncrement, $propUniqueKey*/
				) = $metaData[$metaDataIndex];
				if ($propIsPrivate) {
					$prop = new \ReflectionProperty($context, $propCodeName);
					$prop->setAccessible(TRUE);
					$context->initialValues[$propCodeName] = $prop->getValue($context);
				} else {
					$context->initialValues[$propCodeName] = $context->{$propCodeName};
				}
			}
		}

		return $result;
	}
	
	/**
	 * Process instance database SQL DELETE by automaticly founded key data.
	 * Return `TRUE` if there is removed more than 1 row or return 
	 * `FALSE` if there is no row removed. Thrown an exception in any database error.
	 * @param  \MvcCore\Ext\Models\Db\Model $context 
	 * @param  int                          $propsFlags 
	 * @param  array                        $metaDataCollections 
	 * @throws \InvalidArgumentException|\PDOException|\Throwable
	 * @return bool
	 */
	protected static function editDelete ($context, $propsFlags, $metaDataCollections) {
		list (
			$metaData, /*$autoIncrIndex*/, 
			$primaryKeyColumnsIndexes, $uniqueKeyColumnsIndexes, 
			$connectionArgs, $tableArgs
		) = $metaDataCollections;
		
		$keysColumns = static::getEditAllKeysData(
			$context, $metaData, $primaryKeyColumnsIndexes, $uniqueKeyColumnsIndexes
		);
		
		$editResource = $context->GetEditResource();
		$connectionNameOrIndex = isset($connectionArgs[0]) ? $connectionArgs[0] : NULL;
		list (
			$success, $affectedRows
		) = $editResource->Delete(
			$connectionNameOrIndex, $tableArgs[0], $keysColumns
		);
		
		return $success && $affectedRows > 0;
	}

	/**
	 * Complete all necessary meta data collections 
	 * for any edit operation from cache, once.
	 * @param  int $propsFlags 
	 * @throws \InvalidArgumentException
	 * @return array [$metaData, $autoIncrementIndex, $primaryKeyColumnsIndexes, $uniqueKeyColumnsIndexes, $connectionArgs, $tableArgs]
	 */
	protected static function getEditMetaDataCollections ($propsFlags = 0) {
		$initValuesFlag = \MvcCore\IModel::PROPS_INITIAL_VALUES;
		$hasInitValuesFlag = ($propsFlags & $initValuesFlag) != 0;
		if ($hasInitValuesFlag) // remove init values flag
			$propsFlags = ~((~$propsFlags) | $initValuesFlag);
		$metaDataCollections = static::GetMetaData(
			$propsFlags, [
				\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_AUTO_INCREMENT,
				\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_PRIMARY_KEY,
				\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_UNIQUE_KEY,
				\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_CONNECTIONS,
				\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_TABLES,
				\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_BY_DATABASE,
			]
		);
		if (!isset($metaDataCollections[5]))
			throw new \InvalidArgumentException(
				"[".get_called_class()."] No database table name decorated."
			);
		return $metaDataCollections;
	}

	/**
	 * Try co complete instance key data (for SQL WHERE condition) by anotated 
	 * properties with keys. First, try to complete property/properties with 
	 * primary key(s) anotation, if there is/are value(s) and if it is not 
	 * possible, then try to complete property/properties with unique key 
	 * anotation, if there value(s) exist(s). If there is not possible to 
	 * complete any key data, thrown an invalid argument excaption.
	 * @param  \MvcCore\Ext\Models\Db\Model $context 
	 * @param  array                        $metaData 
	 * @param  array                        $primaryKeyColumnsIndexes 
	 * @param  array                        $uniqueKeyColumnsIndexes 
	 * @throws \InvalidArgumentException 
	 * @return array
	 */
	protected static function getEditAllKeysData ($context, $metaData, $primaryKeyColumnsIndexes, $uniqueKeyColumnsIndexes) {
		list ($identified, $keyColumns) = static::getEditKeysData(
			$context, $metaData, $primaryKeyColumnsIndexes
		);
		if (!$identified) {
			foreach ($uniqueKeyColumnsIndexes as $uniqueKeyColsIndexesItem) {
				if (is_int($uniqueKeyColsIndexesItem)) {
					list ($identified, $keyColumns) = static::getEditKeysData(
						$context, $metaData, [$uniqueKeyColsIndexesItem]
					);
				} else {
					list ($identified, $keyColumns) = static::getEditKeysData(
						$context, $metaData, $uniqueKeyColsIndexesItem
					);
				}
				if ($identified) break;
			}
		}
		if (!$identified) {
			throw new \InvalidArgumentException(
				"[".get_class()."] There was not possible to recognize ".
				"key columns to update/delete model instance. Please define ".
				"property or properties with primary key or unique key(s) ".
				"column(s) attribute(s) anotation(s), where current instance ".
				"has not null or not initialized values."
			);
		}
		return $keyColumns;
	}

	/**
	 * Try to complete key data array from instance for given metadata
	 * anotation collection about primary or unique keys.
	 * Return result as boolean about success key identification and
	 * array with collected key data.
	 * @param  \MvcCore\Ext\Models\Db\Model $context 
	 * @param  array                        $metaData 
	 * @param  array                        $keyColsIndexes 
	 * @return array                        [bool, array]
	 */
	protected static function getEditKeysData ($context, $metaData, $keyColsIndexes) {
		$success = TRUE;
		$keyColumns = [];
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		foreach ($keyColsIndexes as $primaryKeyColIndex) {
			list(
				$propIsPrivate, /*$propAllowNulls*/, /*$propTypes*/, 
				$propCodeName, $propDbColumnName/*, $propParserArgs, $propFormatArgs,
				$propPrimaryKey, $propAutoIncrement, $propUniqueKey*/
			) = $metaData[$primaryKeyColIndex];
			$propValue = NULL;
			if (isset($context->initialValues[$propCodeName])) {
				$propValue = $context->initialValues[$propCodeName];
			} else if ($propIsPrivate) {
				$prop = new \ReflectionProperty($context, $propCodeName);
				$prop->setAccessible(TRUE);
				if ($phpWithTypes) {
					if ($prop->isInitialized($context))
						$propValue = $prop->getValue($context);
				} else {
					$propValue = $prop->getValue($context);
				}
			} else if (isset($context->{$propCodeName})) {
				$propValue = $context->{$propCodeName};
			}
			if ($propValue === NULL) {
				$success = FALSE;
				break;
			} else {
				$keyColumns[$propDbColumnName] = $propValue;
			}
		}
		return [$success, $keyColumns];
	}
}