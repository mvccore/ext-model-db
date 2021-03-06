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

namespace MvcCore\Ext\Models\Db\Providers\Resources;

/**
 * @mixin \MvcCore\Ext\Models\Db\Providers\Resource
 */
trait Manipulation {
	
	/**
	 * Execute SQL code to insert new database table row in transaction, in default database isolation.
	 * @param  int|string  $connNameOrIndex    Connection name or index in system config.
	 * @param  string      $tableName          Database table name.
	 * @param  array       $dataColumns        Data to use in insert clause, keys are 
	 *                                         column names, values are column values.
	 * @param  string      $className          model class full name.
	 * @param  string|NULL $autoIncrColumnName Auto increment column name.
	 * @return array                           First item is boolean result, 
	 *                                         second is affected rows count. 
	 */
	public function Insert ($connNameOrIndex, $tableName, $dataColumns, $className, $autoIncrColumnName) {
		$sqlItems = [];
		$params = [];
		$index = 0;
		$conn = self::GetConnection($connNameOrIndex);

		foreach ($dataColumns as $dataColumnName => $dataColumnValue) {
			$sqlItems[] = $conn->QuoteName($dataColumnName);
			$params[":p{$index}"] = $dataColumnValue;
			$index++;
		}
		
		$tableName = $conn->QuoteName($tableName);
		$sql = "INSERT INTO {$tableName} (" 
			. implode(", ", $sqlItems) 
			. ") VALUES (" 
			. implode(", ", array_keys($params)) 
			. ");";

		$success = FALSE;
		$newId = NULL;
		$error = NULL;

		$execInTransaction = !($conn->InTransaction());

		$transName = 'INSERT:'.str_replace('\\', '_', $className);
		try {
			if ($execInTransaction)
				$conn->BeginTransaction(8 | 16, $transName); // 8 means serializable, 16 means read write

			$reader = $conn
				->Prepare($sql)
				->Execute($params);

			$success = $reader->GetExecResult();
			$affectedRows = $reader->GetRowsCount();

			if ($autoIncrColumnName !== NULL)
				$newId = $conn->LastInsertId();

			if ($execInTransaction)
				$conn->Commit();

			$success = TRUE;
		} catch (\Exception $e) { // backward compatibility
			$affectedRows = 0;
			$newId = NULL;
			$error = $e;
			if ($execInTransaction && $conn->InTransaction())
				$conn->RollBack();
		} catch (\Throwable $e) {
			$affectedRows = 0;
			$newId = NULL;
			$error = $e;
			if ($execInTransaction && $conn->InTransaction())
				$conn->RollBack();
		}

		return [
			$success, 
			$affectedRows,
			$newId,
			$error
		];
	}

	/**
	 * Execute SQL code to update database table row by key columns.
	 * @param  int|string $connNameOrIndex Connection name or index in system config.
	 * @param  string     $tableName       Database table name.
	 * @param  array      $keyColumns      Data to use in where condition, keys are 
	 *                                     column names, values are column values.
	 * @param  array      $dataColumns     Data to use in update set clause, keys are 
	 *                                     column names, values are column values.
	 * @return array                       First item is boolean result, 
	 *                                     second is affected rows count. 
	 */
	public function Update ($connNameOrIndex, $tableName, $keyColumns, $dataColumns) {
		$setSqlItems = [];
		$whereSqlItems = [];
		$params = [];
		$index = 0;
		$conn = self::GetConnection($connNameOrIndex);

		foreach ($dataColumns as $dataColumnName => $dataColumnValue) {
			$setSqlItems[] = $conn->QuoteName($dataColumnName) . " = :p{$index}";
			$params[":p{$index}"] = $dataColumnValue;
			$index++;
		}
		foreach ($keyColumns as $keyColumnName => $keyColumnValue) {
			$whereSqlItems[] = $conn->QuoteName($keyColumnName) . " = :p{$index}";
			$params[":p{$index}"] = $keyColumnValue;
			$index++;
		}

		$tableName = $conn->QuoteName($tableName);
		$sql = "UPDATE {$tableName}"
			. " SET " . implode(", ", $setSqlItems)
			. " WHERE " . implode(" AND ", $whereSqlItems) . ";";
		
		$reader = self::GetConnection($connNameOrIndex)
			->Prepare($sql)
			->Execute($params);

		return [
			$reader->GetExecResult(), 
			$reader->GetRowsCount()
		];
	}

	/**
	 * Execute SQL code to remove database table row.
	 * @param  int|string $connNameOrIndex Connection name or index in system config.
	 * @param  string     $tableName       Database table name.
	 * @param  array      $keyColumns      Data to use in where condition, keys are 
	 *                                     column names, values are column values.
	 * @return array                       First item is boolean result, 
	 *                                     second is affected rows count. 
	 */
	public function Delete ($connNameOrIndex, $tableName, $keyColumns) {
		$sqlItems = [];
		$params = [];
		$index = 0;
		$conn = self::GetConnection($connNameOrIndex);

		foreach ($keyColumns as $keyColumnName => $keyColumnValue) {
			$sqlItems[] = $conn->QuoteName($keyColumnName) . " = :p{$index}";
			$params[":p{$index}"] = $keyColumnValue;
			$index++;
		}

		$tableName = $conn->QuoteName($tableName);
		$sql = "DELETE FROM {$tableName} "
			. "WHERE " . implode(" AND ", $sqlItems) . ";";

		$reader = $conn
			->Prepare($sql)
			->Execute($params);

		return [
			$reader->GetExecResult(), 
			$reader->GetRowsCount()
		];
	}
}