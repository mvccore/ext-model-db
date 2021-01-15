<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Ext\Models\Db;

interface IModel {
	
	/**
	 * Returns `\MvcCore\Ext\Models\Db\Connections` database connection 
	 * by connection name/index, usually by system config values (cached by local store)
	 * or create new connection of no connection cached.
	 * @param string|int|array|\stdClass|NULL $connectionNameOrConfig
	 * @param bool $strict	If `TRUE` and no connection under given name or given
	 *						index found, exception is thrown. `TRUE` by default.
	 *						If `FALSE`, there could be returned connection by
	 *						first available configuration.
	 * @throws \InvalidArgumentException
	 * @return \MvcCore\Ext\Models\Db\Connection
	 */
	public static function GetConnection ($connectionNameOrConfig = NULL, $strict = TRUE);

	/**
	 * Process instance database SQL INSERT or UPDATE by automaticly founded key data.
	 * Return `TRUE` if there is inserted or updated 1 or more rows or return 
	 * `FALSE` if there is no row inserted or updated. Thrown an exception in any database error.
	 * @param $createNew bool|NULL
	 * @param int $propsFlags
	 * @throws \InvalidArgumentException|\Throwable
	 * @return bool
	 */
	public function Save ($createNew = NULL, $propsFlags = 0);

	/**
	 * Try to determinate, if model instance is new or already existing in 
	 * database by property with anotated auto increment column.
	 * If property is not initialized or if it has `NULL` value, than 
	 * model instance is recognized as new and `TRUE` is returned, `FALSE` otherwise.
	 * @param int $propsFlags
	 * @throws \InvalidArgumentException 
	 * @return bool
	 */
	public function IsNew ($propsFlags = 0);

	/**
	 * Process instance database SQL INSERT by automaticly founded key data.
	 * Return `TRUE` if there is updated 1 or more rows or return 
	 * `FALSE` if there is no row updated. Thrown an exception in any database error.
	 * @param int $propsFlags
	 * @throws \InvalidArgumentException|\Throwable
	 * @return bool
	 */
	public function Insert ($propsFlags = 0);

	/**
	 * Process instance database SQL UPDATE by automaticly founded key data.
	 * Return `TRUE` if there is updated 1 or more rows or return 
	 * `FALSE` if there is no row updated. Thrown an exception in any database error.
	 * @param int $propsFlags
	 * @throws \InvalidArgumentException|\Throwable
	 * @return bool
	 */
	public function Update ($propsFlags = 0);

	/**
	 * Process instance database SQL DELETE by automaticly founded key data.
	 * Return `TRUE` if there is removed more than 1 row or return 
	 * `FALSE` if there is no row removed. Thrown an exception in any database error.
	 * @param int $propsFlags 
	 * @throws \InvalidArgumentException|\Throwable
	 * @return bool
	 */
	public function Delete ($propsFlags = 0);
}