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

class		Batch
extends		\MvcCore\Ext\Models\Db\Model
implements	\MvcCore\Ext\Models\Db\IBatch {

	use \MvcCore\Ext\Models\Db\Model\EditMethods;

	/**
	 * Automaticaly flush batch after flush size is exceeded.
	 * @var bool
	 */
	protected $autoFlush = TRUE;
	
	/**
	 * Default flush size is `10` items.
	 * @var int
	 */
	protected $flushSize = 10;
	
	/**
	 * Current instances array size.
	 * @var int
	 */
	protected $size = 0;

	/**
	 * Instances store.
	 * @var \MvcCore\Ext\Models\Db\Model[]
	 */
	protected $instances = [];

	/**
	 * Operations flags to execute on collected instances.
	 * @var \int[]
	 */
	protected $operationsFlags = [];

	/**
	 * Connection name or index.
	 * @var string|int|NULL
	 */
	protected $connectionName = NULL;

	/**
	 * Connection instance.
	 * @var \MvcCore\Ext\Models\Db\Connection|NULL
	 */
	protected $connection = NULL;

	/**
	 * Flush execution data store.
	 * @var \MvcCore\Ext\Models\Db\Batches\FlushData|NULL
	 */
	protected $flushData = NULL;

	/**
	 * Affected rows for all queries in last flush call.
	 * @var int
	 */
	protected $allResultsRowsCount = 0;

	/**
	 * Inserted instances metadata cache.
	 * @var array
	 */
	protected $instancesMetaData = [];
	
	/**
	 * Set connection instance.
	 * @param  string|int|NULL $connection 
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function SetConnectionName ($connectionName = NULL) {
		$this->connectionName = $connectionName;
		return $this;
	}
	
	/**
	 * 
	 * @param  int|NULL $flushSize 
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function SetAutoFlushSize ($flushSize) {
		$this->flushSize = $flushSize;
		$this->autoFlush = is_int($flushSize);
		return $this;
	}
	
	/**
	 * 
	 * @return int|NULL
	 */
	public function GetAutoFlushSize () {
		return $this->flushSize;
	}
	
	/**
	 * @inheritDocs
	 * @return int
	 */
	public function GetSize () {
		return $this->size;
	}

	/**
	 * @inheritDocs
	 * @param  \MvcCore\Ext\Models\Db\Model $modelInstance 
	 * @param  int                          $operationFlags 
	 * @throws \InvalidArgumentException    Model instance is already in batch.
	 * @throws \Exception                   Any exception thrown by `Flush()` method.
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function AddToBatch (\MvcCore\Ext\Models\Db\IModel $modelInstance, $operationFlags = \MvcCore\Ext\Models\Db\IBatch::OPERATION_SAVE) {
		if (in_array($modelInstance, $this->instances, TRUE)) 
			throw new \InvalidArgumentException(
				"[".get_class($this)."] Model instance is already in batch."
			);
		$this->instances[] = $modelInstance;
		$this->operationsFlags[] = $operationFlags;
		$this->size += 1;
		if ($this->autoFlush && $this->size === $this->flushSize)
			return $this->Flush();
		return $this;
	}
	
	/**
	 * @inheritDocs
	 * @throws \Exception Database execution exception.
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function Flush () {
		if ($this->connection === NULL)
			$this->connection = self::GetConnection($this->connectionName);
		
		$this->flushPrepare();
		$this->flushExecute();
		$this->flushCleanUp();
		
		return $this;
	}
	
	public function BatchEditResourceHandler ($sqlOperation, $sqlCode, $params) {
		$flushData = & $this->flushData;
		$flushData->InstanceIndexes[] = $flushData->InstanceIndex;
		$flushData->SqlOperations[] = $sqlOperation;
		$flushData->SqlCodes[] = $sqlCode;
		$flushData->Params = $flushData->Params + $params;
		if ($flushData->UseMetaStatement) {
			$flushData->InstanceIndexes[] = $flushData->InstanceIndex;
			$flushData->SqlOperations[] = 0;
			$flushData->SqlCodes[] = $flushData->MetaStatement;
		}
	}

	protected function flushPrepare () {
		$this->allResultsRowsCount = 0;

		$metaStatement = $this->connection->GetMetaDataStatement();

		$this->flushData = new \MvcCore\Ext\Models\Db\Batches\FlushData($metaStatement);
		
		$batchEditResource = new \MvcCore\Ext\Models\Db\Batches\EditResource;
		$batchEditResource->ResetParamsCounter();
		$batchEditResource->SetEditHandler([$this, 'BatchEditResourceHandler']);
		
		foreach ($this->instances as $index => $instance) {
			$instanceEditRes = $instance->GetEditResource(FALSE);
			$instance->SetEditResource($batchEditResource);
			$operationFlags = $this->operationsFlags[$index];
			if (($operationFlags & \MvcCore\Ext\Models\Db\IBatch::OPERATION_SAVE) != 0) {
				$instance->Save();
			} else if (($operationFlags & \MvcCore\Ext\Models\Db\IBatch::OPERATION_INSERT) != 0) {
				$instance->Insert();
			} else if (($operationFlags & \MvcCore\Ext\Models\Db\IBatch::OPERATION_UPDATE) != 0) {
				$instance->Update();
			} else if (($operationFlags & \MvcCore\Ext\Models\Db\IBatch::OPERATION_DELETE) != 0) {
				$instance->Delete();
			}
			$instance->SetEditResource($instanceEditRes);
			$this->flushData->InstanceIndex++;
		}
	}

	protected function flushExecute () {
		$flushData = & $this->flushData;
		$multiStatement = $this->connection->Prepare(
			$flushData->SqlCodes, 
			[\MvcCore\Ext\Models\Db\IStatement::DO_NOT_AUTO_CLOSE]
		);
		$pdoMultiStatement = $multiStatement->GetProviderStatement();
		$multiStatement->Execute($flushData->Params);

		$sqlOperationIndex = 0;
		$sqlOperationsCount = count($flushData->SqlOperations);
		$sqlOperation = NULL;
		$prevSqlOperation = NULL;
		while ($sqlOperationIndex < $sqlOperationsCount) {
			$prevSqlOperation = $sqlOperation;
			$sqlOperation = $flushData->SqlOperations[$sqlOperationIndex];
			$instanceIndex = $flushData->InstanceIndexes[$sqlOperationIndex];

			if ($sqlOperation === 0) {
				$metaData = $pdoMultiStatement->fetch(\PDO::FETCH_ASSOC);
				$rowCount = intval($metaData['AffectedRows']);

				$operationFlags = $this->operationsFlags[$instanceIndex];

				if ($prevSqlOperation === \MvcCore\Ext\Models\Db\IBatch::OPERATION_INSERT) {
					$this->setUpInsertedInstanceId(
						$this->instances[$instanceIndex],
						$metaData['LastInsertId'],
						($operationFlags & \MvcCore\IModel::PROPS_INITIAL_VALUES) != 0
					);
				}
				$this->allResultsRowsCount += $rowCount;

			} else if (!$flushData->UseMetaStatement) {
				$rowCount = $pdoMultiStatement->rowCount();
				$this->allResultsRowsCount += $rowCount;
			}
			if (!$pdoMultiStatement->nextRowset()) break;
			$sqlOperationIndex++;
		}
	}

	protected function flushCleanUp () {
		$this->flushData = NULL;
		$this->instances = [];
		$this->operationsFlags = [];
		$this->size = 0;
		$this->instancesMetaData = [];
	}

	protected function setUpInsertedInstanceId (
		\MvcCore\Ext\Models\Db\IModel $insertedInstance, 
		$rawNewId,
		$completeInitialValues
	) {
		$instanceClass = get_class($insertedInstance);
		if (isset($this->instancesMetaData[$instanceClass])) {
			$metaDataCollections = $this->instancesMetaData[$instanceClass];
		} else {
			$metaDataCollections = $insertedInstance::GetMetaData(
				0, [\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_AUTO_INCREMENT]
			);
		}
		list ($metaData, $autoIncrIndex) = $metaDataCollections;
		
		$hasAutoIncrColumn = isset($metaData[$autoIncrIndex]);
		if ($hasAutoIncrColumn) {
			list(
				/*$autoIncrPropIsPrivate*/, /*$autoIncrPropAllowNulls*/, $autoIncrPropTypes, 
				$autoIncrPropCodeName/*, $autoIncrPropDbName, $autoIncrPropFormatArgs,
				$autoIncrPropPrimaryKey, $autoIncrPropAutoIncrement, $autoIncrPropUniqueKey*/
			) = $metaData[$autoIncrIndex];

			$newId = static::parseToTypes($rawNewId, $autoIncrPropTypes);
						
			$prop = new \ReflectionProperty($insertedInstance, $autoIncrPropCodeName);
			$prop->setAccessible(TRUE);
			$prop->setValue($insertedInstance, $newId);

			if ($completeInitialValues) {
				$initValuesProp = new \ReflectionProperty($insertedInstance, 'initialValues');
				$initValuesProp->setAccessible(TRUE);
				$initialValues = $initValuesProp->getValue($insertedInstance);
				$initialValues[$autoIncrPropCodeName] = $newId;
				$initValuesProp->setValue($insertedInstance, $initialValues);
			}
		}
	}
	
	/**
	 * @inheritDocs
	 * @return int
	 */
	public function GetAllResultsRowsCount () {
		return $this->allResultsRowsCount;
	}
}