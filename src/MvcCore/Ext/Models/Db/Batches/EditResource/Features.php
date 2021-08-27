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

namespace MvcCore\Ext\Models\Db\Batches\EditResource;

/**
 * @mixin \MvcCore\Ext\Models\Db\Batches\EditResource
 */
trait Features {
	
	/**
	 * Batch params counter.
	 */
	protected $paramsCounter = 0;
	
	/**
	 * @var `\Closure` function returning void and with arguments:
	 *  - string $operationSql
	 *  - array  $operationParams
	 *  - string $fetchSql
	 *  - array  $fetchParams
	 */
	protected $editHandler;
	
	public function ResetParamsCounter () {
		$this->paramsCounter = 0;
		return $this;
	}

	public function SetEditHandler ($editHandler) {
		$this->editHandler = $editHandler;
		return $this;
	}

	/**
	 * @inheritDocs
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
		$operationParams = [];
		$conn = self::GetConnection($connNameOrIndex);

		foreach ($dataColumns as $dataColumnName => $dataColumnValue) {
			$sqlItems[] = $conn->QuoteName($dataColumnName);
			$operationParams[":p{$this->paramsCounter}"] = $dataColumnValue;
			$this->paramsCounter++;
		}
		
		$tableName = $conn->QuoteName($tableName);
		$operationSql = "INSERT INTO {$tableName} (" 
			. implode(", ", $sqlItems) 
			. ") VALUES (" 
			. implode(", ", array_keys($operationParams)) 
			. ");";

		$fetchSql = NULL;
		$fetchParams = [];
		if ($autoIncrColumnName !== NULL && $conn->GetUsingOdbcDriver()) {
			// odbc driver doesn't support function `LastInsertId()`, it needs fetch sql after each insert:
			$autoIncrColumnName = $conn->QuoteName($autoIncrColumnName);
			$fetchSql = "SELECT MAX({$autoIncrColumnName}) AS LastInsertedId FROM {$tableName};";
		}

		$editHandler = $this->editHandler;
		$editHandler($operationSql, $operationParams, $fetchSql, $fetchParams);

		return [TRUE, 1];
	}

	/**
	 * @inheritDocs
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
		$operationParams = [];
		$conn = self::GetConnection($connNameOrIndex);

		foreach ($dataColumns as $dataColumnName => $dataColumnValue) {
			$setSqlItems[] = $conn->QuoteName($dataColumnName) . " = :p{$this->paramsCounter}";
			$operationParams[":p{$this->paramsCounter}"] = $dataColumnValue;
			$this->paramsCounter++;
		}
		foreach ($keyColumns as $keyColumnName => $keyColumnValue) {
			$whereSqlItems[] = $conn->QuoteName($keyColumnName) . " = :p{$this->paramsCounter}";
			$operationParams[":p{$this->paramsCounter}"] = $keyColumnValue;
			$this->paramsCounter++;
		}

		$tableName = $conn->QuoteName($tableName);
		$operationSql = "UPDATE {$tableName}"
			. " SET " . implode(", ", $setSqlItems)
			. " WHERE " . implode(" AND ", $whereSqlItems) . ";";
		
		$editHandler = $this->editHandler;
		$editHandler($operationSql, $operationParams);

		return [TRUE, 1];
	}

	/**
	 * @inheritDocs
	 * @param  int|string $connNameOrIndex Connection name or index in system config.
	 * @param  string     $tableName       Database table name.
	 * @param  array      $keyColumns      Data to use in where condition, keys are 
	 *                                     column names, values are column values.
	 * @return array                       First item is boolean result, 
	 *                                     second is affected rows count. 
	 */
	public function Delete ($connNameOrIndex, $tableName, $keyColumns) {
		$sqlItems = [];
		$operationParams = [];
		$conn = self::GetConnection($connNameOrIndex);

		foreach ($keyColumns as $keyColumnName => $keyColumnValue) {
			$sqlItems[] = $conn->QuoteName($keyColumnName) . " = :p{$this->paramsCounter}";
			$operationParams[":p{$this->paramsCounter}"] = $keyColumnValue;
			$this->paramsCounter++;
		}

		$tableName = $conn->QuoteName($tableName);
		$operationSql = "DELETE FROM {$tableName} "
			. "WHERE " . implode(" AND ", $sqlItems) . ";";

		$editHandler = $this->editHandler;
		$editHandler($operationSql, $operationParams);

		return [TRUE, 1];
	}
}