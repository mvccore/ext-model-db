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

/**
 * Responsibility - model instance for batch operations with entities
 *                  extended from `\MvcCore\Ext\Models\Db\Model` without
 *                  not extended methods `Insert()`, `Update()` or `Delete()`.
 */
interface IBatch {

	/**
	 * Operation to insert given model instance into database.
	 * @var int
	 */
	const OPERATION_INSERT		= 16384;
	
	/**
	 * Operation to update given model instance in database.
	 * @var int
	 */
	const OPERATION_UPDATE		= 32768;
	
	/**
	 * Operation to insert or update given model instance into/in database.
	 * @var int
	 */
	const OPERATION_SAVE		= 49152;
	
	/**
	 * Operation to delete given model instance from database.
	 * @var int
	 */
	const OPERATION_DELETE		= 65536;


	/**
	 * Default batch instance edit resource type.
	 * @var string
	 */
	const BATCH_INSTANCE_EDIT_RESOURCE_TYPE = 'MvcCore\\Ext\\Models\\Db\\Batchs\\EditResource';

	/**
	 * Batch instance edit resource interface.
	 * @var string
	 */
	const BATCH_INSTANCE_EDIT_RESOURCE_INTERFACE = 'MvcCore\\Ext\\Models\\Db\\Batchs\\IEditResource';
	
	/**
	 * Get automatic batch flush size. Default value is 10 items. 
	 * If `NULL`, flushing is not called automatically 
	 * and it's necessary to call `$batchModel->Flush()` manually.
	 * @return ?int
	 */
	public function GetAutoFlushSize ();

	/**
	 * Set automatic batch flush size. Default value is 10 items. 
	 * If `NULL` or `0`, flushing will not be called automatically 
	 * and it's necessary to call `$batchModel->Flush()` manually.
	 * @param  ?int $flushSize 
	 * @return \MvcCore\Ext\Models\Db\IBatch
	 */
	public function SetAutoFlushSize ($flushSize);
	
	/**
	 * Get current count of model instances in batch.
	 * @return int
	 */
	public function GetSize ();

	/**
	 * Get batch instances.
	 * @return \MvcCore\Ext\Models\Db\IModel[]
	 */
	public function GetInstances ();

	/**
	 * Return affected rows by all queries in last flush call.
	 * @return int
	 */
	public function GetRowsCount ();

	/**
	 * Set custom connection name or index.
	 * @param  string|int|null $connection 
	 * @return \MvcCore\Ext\Models\Db\IBatch
	 */
	public function SetConnectionName ($connectionName = NULL);

	/**
	 * Get if batch has given model instance already or not.
	 * When batch model is flushed, all model instance references are unset.
	 * @param  \MvcCore\Ext\Models\Db\Model $modelInstance 
	 * @return bool
	 */
	public function HasInBatch (\MvcCore\Ext\Models\Db\IModel $modelInstance);
	
	/**
	 * Remove model instance from batch if there is any.
	 * When batch model is flushed, all model instance references are unset.
	 * @param  \MvcCore\Ext\Models\Db\Model $modelInstance 
	 * @return \MvcCore\Ext\Models\Db\IBatch
	 */
	public function RemoveFromBatch (\MvcCore\Ext\Models\Db\IModel $modelInstance);

	/**
	 * Add model instance into batch. When batch model 
	 * is flushed, all model instance references are unset.
	 * Default operation is to save model instance, it means 
	 * to insert or update, it depends on instance.
	 * @param  \MvcCore\Ext\Models\Db\IModel $modelInstance 
	 * @param  int                           $operation 
	 * @throws \InvalidArgumentException     Model instance is already in batch.
	 * @throws \Exception                    Any exception thrown by `Flush()` method.
	 * @return \MvcCore\Ext\Models\Db\IBatch
	 */
	public function AddToBatch (\MvcCore\Ext\Models\Db\IModel $modelInstance, $operation = \MvcCore\Ext\Models\Db\IBatch::OPERATION_SAVE);

	/**
	 * Prepare SQL codes and params for all model instances 
	 * in batch and execute all operations in one database call.
	 * @throws \Exception Database execution exception.
	 * @return \MvcCore\Ext\Models\Db\IBatch
	 */
	public function Flush ();

	/**
	 * Handle prepared INSERT/UPDATE/DELETE operation, SQL query and params
	 * from model instance edit resource and store it into local array for 
	 * later execution.
	 * @inheritDoc
	 * @param  int    $sqlOperation 
	 * @param  string $sqlCode 
	 * @param  array  $params 
	 * @return void
	 */
	public function BatchEditResourceHandler ($sqlOperation, $sqlCode, $params);

	/**
	 * Set editing resource type of the model instance in the batch. 
	 * An instance of this type is inserted into each instance of the model 
	 * in the batch before the command is executed to the database.
	 * @param  string $instanceEditResourceType 
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function SetInstanceEditResourceType ($instanceEditResourceType);

	/**
	 * Get editing resource type of the model instance in the batch. 
	 * An instance of this type is inserted into each instance of the model 
	 * in the batch before the command is executed to the database.
	 * @return \Reflection
	 */
	public function GetInstanceEditResourceType ();

	/**
	 * Set constructor arguments for new instance of editing resource.
	 * @param  array $ctorArgs 
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function SetInstanceEditResourceCtorArgs (array $ctorArgs);

	/**
	 * Get constructor arguments for new instance of editing resource.
	 * @return array
	 */
	public function GetInstanceEditResourceCtorArgs ();
}