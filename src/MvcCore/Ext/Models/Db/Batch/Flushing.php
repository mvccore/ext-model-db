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

namespace MvcCore\Ext\Models\Db\Batch;

/**
 * @mixin \MvcCore\Ext\Models\Db\Batch
 * @property $flushData \MvcCore\Ext\Models\Db\Batchs\FlushData
 */
trait Flushing {
	
	/**
	 * @inheritDocs
	 * @throws \Exception Database execution exception.
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function Flush () {
		/** @var \MvcCore\Ext\Models\Db\Batch $this */
		if ($this->size === 0) return $this;
		if ($this->connection === NULL)
			$this->connection = self::GetConnection($this->connectionName);
		$this->rowsCount = 0;
		while ($this->size > 0) {
			$this->flushPrepare();
			$this->flushExecute();
			$this->flushCleanUp();
		}
		return $this;
	}
	
	/**
	 * @inheritDocs
	 * @param  int    $sqlOperation 
	 * @param  string $sqlCode 
	 * @param  array  $params 
	 * @return void
	 */
	public function BatchEditResourceHandler ($sqlOperation, $sqlCode, $params) {
		/** @var \MvcCore\Ext\Models\Db\Batch $this */
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

	/**
	 * Prepare SQL statements for all instance operations in batch model.
	 * @return void
	 */
	protected function flushPrepare () {
		/** @var \MvcCore\Ext\Models\Db\Batch $this */
		$metaStatement = $this->connection->GetMetaDataStatement();

		$this->flushData = new \MvcCore\Ext\Models\Db\Batchs\FlushData($metaStatement);
		
		$batchEditResource = new \MvcCore\Ext\Models\Db\Batchs\EditResource;
		$batchEditResource->ResetParamsCounter();
		$batchEditResource->SetEditHandler([$this, 'BatchEditResourceHandler']);
		
		foreach ($this->instances as $index => $instance) {
			$instanceEditRes = $instance->GetEditResource(FALSE);
			$instance->SetEditResource($batchEditResource);
			$operationFlags = $this->operationsFlags[$index];
			if (($operationFlags & \MvcCore\Ext\Models\Db\IBatch::OPERATION_INSERT) != 0) {
				$instance->Insert();
			} else if (($operationFlags & \MvcCore\Ext\Models\Db\IBatch::OPERATION_UPDATE) != 0) {
				$instance->Update();
			} else if (($operationFlags & \MvcCore\Ext\Models\Db\IBatch::OPERATION_SAVE) != 0) {
				$instance->Save();
			} else if (($operationFlags & \MvcCore\Ext\Models\Db\IBatch::OPERATION_DELETE) != 0) {
				$instance->Delete();
			}
			$instance->SetEditResource($instanceEditRes);
			$this->flushData->InstanceIndex++;
			if (!$this->autoFlush && $this->flushData->InstanceIndex === $this->flushSize) {
				break;
			}
		}
	}

	/**
	 * Execute prepared SQL statements and fetch all ids and rows counts.
	 * @throws \Exception Database execution exception.
	 * @return void
	 */
	protected function flushExecute () {
		/** @var \MvcCore\Ext\Models\Db\Batch $this */
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
				$this->rowsCount += $rowCount;

			} else if (!$flushData->UseMetaStatement) {
				$rowCount = $pdoMultiStatement->rowCount();
				$this->rowsCount += $rowCount;
			}
			if (!$pdoMultiStatement->nextRowset()) break;
			$sqlOperationIndex++;
		}
	}

	/**
	 * Set local properties to start position.
	 * @return void
	 */
	protected function flushCleanUp () {
		$instanceIndex = $this->flushData->InstanceIndex;
		$this->instances = array_slice($this->instances, $instanceIndex);
		$this->operationsFlags = array_slice($this->operationsFlags, $instanceIndex);
		$this->size = count($this->instances);
		if ($this->size === 0)
			$this->instancesMetaData = [];
		$this->flushData = NULL;
	}

	/**
	 * If model instance in batch has been inserted
	 * and it has auto increment column - set up new 
	 * id into this model instance.
	 * @param  \MvcCore\Ext\Models\Db\IModel $insertedInstance 
	 * @param  mixed $rawNewId 
	 * @param  bool $completeInitialValues 
	 * @return void
	 */
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
				$autoIncrPropCodeName/*, $autoIncrPropDbName, $autoIncrPropParserArgs, $autoIncrPropFormatArgs,
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
}