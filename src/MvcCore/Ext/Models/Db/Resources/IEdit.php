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

namespace MvcCore\Ext\Models\Db\Resources;

interface IEdit {

	/**
	 * Execute SQL code to insert new database table row in transaction, in default database isolation.
	 * @param  int|string $connNameOrIndex    Connection name or index in system config.
	 * @param  string     $tableName          Database table name.
	 * @param  array      $dataColumns        Data to use in insert clause, keys are 
	 *                                        column names, values are column values.
	 * @param  string     $className          model class full name.
	 * @param  ?string    $autoIncrColumnName Auto increment column name.
	 * @return array                          First item is boolean result, 
	 *                                        second is affected rows count. 
	 */
	public function Insert ($connNameOrIndex, $tableName, $dataColumns, $className, $autoIncrColumnName);

	/**
	 * Execute SQL code to update database table row by key columns.
	 * @param  int|string $connNameOrIndex Connection name or index in system config.
	 * @param  string     $tableName       Database table name.
	 * @param  array      $keyColumns      Data to use in where condition, keys are 
	 *                                     column names, values are column values.
	 * @param  array      $dataColumns     Data to use in update set clause, keys are 
	 *                                     column names, values are column values.
	 * @throws \PDOException|\Throwable
	 * @return array                       First item is boolean result, 
	 *                                     second is affected rows count. 
	 */
	public function Update ($connNameOrIndex, $tableName, $keyColumns, $dataColumns);

	/**
	 * Execute SQL code to remove database table row.
	 * @param  int|string $connNameOrIndex Connection name or index in system config.
	 * @param  string     $tableName       Database table name.
	 * @param  array      $keyColumns      Data to use in where condition, keys are 
	 *                                     column names, values are column values.
	 * @throws \PDOException|\Throwable
	 * @return array                       First item is boolean result, 
	 *                                     second is affected rows count. 
	 */
	public function Delete ($connNameOrIndex, $tableName, $keyColumns);
}