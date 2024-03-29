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

namespace MvcCore\Ext\Models\Db\Batchs\EditResource;

/**
 * @mixin \MvcCore\Ext\Models\Db\Batchs\EditResource
 */
trait Features {
	
	/**
	 * Batch params counter.
	 * @var int
	 */
	protected $paramsCounter = 0;
	
	/**
	 * Batch edit handler to handle completed operation type, SQL and params.
	 * `\Closure` function returning void and accepting arguments:
	 *  - int $sqlOperation - type of SQL operation by enum `IBatch::OPERATION_*`,
	 *  - string $sqlCode   - raw sql code to execute operation,
	 *  - array $params     - database operation params.
	 * @var callable|\Closure
	 */
	protected $editHandler;
	
	/**
	 * @inheritDoc
	 * @return \MvcCore\Ext\Models\Db\Batchs\EditResource
	 */
	public function ResetParamsCounter () {
		$this->paramsCounter = 0;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  callable|\Closure $editHandler 
	 * @return \MvcCore\Ext\Models\Db\Batchs\EditResource
	 */
	public function SetEditHandler ($editHandler) {
		$this->editHandler = $editHandler;
		return $this;
	}

	/**
	 * @inheritDoc
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
		$conn = self::GetConnection($connNameOrIndex);

		foreach ($dataColumns as $dataColumnName => $dataColumnValue) {
			$sqlItems[] = $conn->QuoteName($dataColumnName);
			$params[":p{$this->paramsCounter}"] = $dataColumnValue;
			$this->paramsCounter++;
		}
		
		$tableName = $conn->QuoteName($tableName);
		$sql = "INSERT INTO {$tableName} (" 
			. implode(", ", $sqlItems) 
			. ") VALUES (" 
			. implode(", ", array_keys($params)) 
			. ");";

		call_user_func_array(
			$this->editHandler, 
			[\MvcCore\Ext\Models\Db\IBatch::OPERATION_INSERT, $sql, $params]
		);

		return [FALSE, 0, NULL, NULL];
	}

	/**
	 * @inheritDoc
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
		$conn = self::GetConnection($connNameOrIndex);

		foreach ($dataColumns as $dataColumnName => $dataColumnValue) {
			$setSqlItems[] = $conn->QuoteName($dataColumnName) . " = :p{$this->paramsCounter}";
			$params[":p{$this->paramsCounter}"] = $dataColumnValue;
			$this->paramsCounter++;
		}
		foreach ($keyColumns as $keyColumnName => $keyColumnValue) {
			$whereSqlItems[] = $conn->QuoteName($keyColumnName) . " = :p{$this->paramsCounter}";
			$params[":p{$this->paramsCounter}"] = $keyColumnValue;
			$this->paramsCounter++;
		}

		$tableName = $conn->QuoteName($tableName);
		$sql = "UPDATE {$tableName}"
			. " SET " . implode(", ", $setSqlItems)
			. " WHERE " . implode(" AND ", $whereSqlItems) . ";";
		
		call_user_func_array(
			$this->editHandler, 
			[\MvcCore\Ext\Models\Db\IBatch::OPERATION_UPDATE, $sql, $params]
		);

		return [FALSE, 0];
	}

	/**
	 * @inheritDoc
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
		$conn = self::GetConnection($connNameOrIndex);

		foreach ($keyColumns as $keyColumnName => $keyColumnValue) {
			$sqlItems[] = $conn->QuoteName($keyColumnName) . " = :p{$this->paramsCounter}";
			$params[":p{$this->paramsCounter}"] = $keyColumnValue;
			$this->paramsCounter++;
		}

		$tableName = $conn->QuoteName($tableName);
		$sql = "DELETE FROM {$tableName} "
			. "WHERE " . implode(" AND ", $sqlItems) . ";";

		call_user_func_array(
			$this->editHandler, 
			[\MvcCore\Ext\Models\Db\IBatch::OPERATION_DELETE, $sql, $params]
		);

		return [FALSE, 0];
	}
}