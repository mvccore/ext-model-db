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

namespace MvcCore\Ext\Models\Db;

interface IModel {
	
	/**
	 * Returns `\MvcCore\Ext\Models\Db\Connections` database connection 
	 * by connection name/index, usually by system config values (cached by local store)
	 * or create new connection of no connection cached.
	 * @param  string|int|array|\stdClass|NULL $connectionNameOrConfig
	 * @param  bool                            $strict
	 *                                         If `TRUE` and no connection under given name or given
	 *                                         index found, exception is thrown. `TRUE` by default.
	 *                                         If `FALSE`, there could be returned connection by
	 *                                         first available configuration.
	 * @throws \InvalidArgumentException
	 * @return \MvcCore\Ext\Models\Db\Connection
	 */
	public static function GetConnection ($connectionNameOrConfig = NULL, $strict = TRUE);
	
	/**
	 * Returns (or creates if necessary) model resource instance.
	 * Common resource instance is stored all the time in static store
	 * under key from resource full class name and constructor arguments.
	 * @param  array|NULL $args      Values array with variables to pass into resource `__construct()` method.
	 *                               If `NULL`, recource class will be created without `__construct()` method call.
	 * @param  string     $classPath Relative namespace path to resource class. It could contains `.` or `..`
	 *                               to traverse over namespaces (directories) and it could contains `{self}` 
	 *                               keyword, which is automatically replaced with current class name.
	 * @thrown \InvalidArgumentException Class `{$resourceClassName}` doesn't exist.
	 * @return \MvcCore\Ext\Models\Db\Resource
	 */
	public static function GetCommonResource ($args = NULL, $classPath = '{self}s\CommonResource');
	
	/**
	 * Returns (or creates if doesn`t exist) model resource instance.
	 * Resource instance is stored in protected instance property `resource`.
	 * @param  array|NULL $args      Values array with variables to pass into resource `__construct()` method.
	 *                               If `NULL`, recource class will be created without `__construct()` method call.
	 * @param  string     $classPath Relative namespace path to resource class. It could contains `.` or `..`
	 *                               to traverse over namespaces (directories) and it could contains `{self}` 
	 *                               keyword, which is automatically replaced with current class name.
	 * @thrown \InvalidArgumentException Class `{$resourceClassName}` doesn't exist.
	 * @return \MvcCore\Ext\Models\Db\Resource
	 */
	public function GetResource ($args = NULL, $classPath = '{self}s\Resource');

	/**
	 * Return cached data about properties in current class to not create
	 * and parse reflection objects every time. Be carefull, meta data are 
	 * in lowest level as it could be - only in array types, to serialize or 
	 * unserialize them into or from cache as fast as possible instead of 
	 * serializing PHP objects.
	 * 
	 * Returned result is different from parent method, result is array to list 
	 * separate variables. First result variable is always metadata array with 
	 * numeric indexes, where each value is property metadata like in parent 
	 * method result. Second and every next result record is properties map, 
	 * where keys are properties names (or columns names) and values are integer 
	 * keys into first result variable with metadata.
	 * 
	 * Every key in metadata array in first result variable is integer key, 
	 * which is necessary to complete from any properties map and every value
	 * is array with metadata:
	 * - `0`    `boolean`           `TRUE` for private property.
	 * - `1'    `boolean`           `TRUE` to allow `NULL` values.
	 * - `2`    `string[]`          Property types from code or from doc comments or empty array.
	 * - `3`    `string`            PHP code property name.
	 * - `4`    `string|NULL`       Database column name (if defined) or `NULL`.
	 * - `5`    `mixed`             Additional convertsion data (if defined) or `NULL`.
	 * - `6`    `bool`              `TRUE` if column is in primary key.
	 * - `7`    `bool`              `TRUE` if column has auto increment feature.
	 * - `8`    `bool|string|NULL`  `TRUE` if column is in unique key or name 
	 *                              of the unique key in database.
	 *                              private properties manipulation.
	 * - `9`    `bool`              `TRUE` if property has defined default value.
	 * 
	 * Possible reading flags:
	 *  - `\MvcCore\IModel::PROPS_INHERIT`
	 *  - `\MvcCore\IModel::PROPS_PRIVATE`
	 *  - `\MvcCore\IModel::PROPS_PROTECTED`
	 *  - `\MvcCore\IModel::PROPS_PUBLIC`
	 * @param  int    $propsFlags
	 * @param  \int[] $additionalMaps
	 * @throws \RuntimeException|\InvalidArgumentException
	 * @return array
	 */
	public static function GetMetaData ($propsFlags = 0, $additionalMaps = []);

	/**
	 * Process instance database SQL INSERT or UPDATE by automaticly founded key data.
	 * Return `TRUE` if there is inserted or updated 1 or more rows or return 
	 * `FALSE` if there is no row inserted or updated. Thrown an exception in any database error.
	 * @param  bool|NULL $createNew 
	 * @param  int       $propsFlags
	 * @throws \InvalidArgumentException|\Throwable
	 * @return bool
	 */
	public function Save ($createNew = NULL, $propsFlags = 0);

	/**
	 * Try to determinate, if model instance is new or already existing in 
	 * database by property with anotated auto increment column.
	 * If property is not initialized or if it has `NULL` value, than 
	 * model instance is recognized as new and `TRUE` is returned, `FALSE` otherwise.
	 * @param  int $propsFlags
	 * @throws \InvalidArgumentException 
	 * @return bool
	 */
	public function IsNew ($propsFlags = 0);

	/**
	 * Process instance database SQL INSERT by automaticly founded key data.
	 * Return `TRUE` if there is updated 1 or more rows or return 
	 * `FALSE` if there is no row updated. Thrown an exception in any database error.
	 * @param  int $propsFlags
	 * @throws \InvalidArgumentException|\Throwable
	 * @return bool
	 */
	public function Insert ($propsFlags = 0);

	/**
	 * Process instance database SQL UPDATE by automaticly founded key data.
	 * Return `TRUE` if there is updated 1 or more rows or return 
	 * `FALSE` if there is no row updated. Thrown an exception in any database error.
	 * @param  int $propsFlags
	 * @throws \InvalidArgumentException|\Throwable
	 * @return bool
	 */
	public function Update ($propsFlags = 0);

	/**
	 * Process instance database SQL DELETE by automaticly founded key data.
	 * Return `TRUE` if there is removed more than 1 row or return 
	 * `FALSE` if there is no row removed. Thrown an exception in any database error.
	 * @param  int $propsFlags 
	 * @throws \InvalidArgumentException|\Throwable
	 * @return bool
	 */
	public function Delete ($propsFlags = 0);

	/**
	 * Get database provider specific resource with universal SQL statements
	 * to automatically insert, update and delete model instance.
	 * @param  bool $autoCreate
	 * @return \MvcCore\Ext\Models\Db\Resources\IEdit|NULL
	 */
	public function GetEditResource ($autoCreate = TRUE);

	/**
	 * Set database provider specific resource with universal SQL statements
	 * to automatically insert, update and delete model instance.
	 * @param  \MvcCore\Ext\Models\Db\Resources\IEdit|NULL
	 * @return \MvcCore\Ext\Models\Db\IModel
	 */
	public function SetEditResource (\MvcCore\Ext\Models\Db\Resources\IEdit $editResource = NULL);
}