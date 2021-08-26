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

interface IBatch {

	/**
	 * Operation to insert given model instance into database.
	 * @var int
	 */
	const OPERATION_INSERT	= 1;
	
	/**
	 * Operation to update given model instance in database.
	 * @var int
	 */
	const OPERATION_UPDATE	= 2;
	
	/**
	 * Operation to insert or update given model instance into/in database.
	 * @var int
	 */
	const OPERATION_SAVE	= 3;
	
	/**
	 * Operation to delete given model instance from database.
	 * @var int
	 */
	const OPERATION_DELETE	= 4;


	/**
	 * Set automatic batch flush size. Default value is 10 items. 
	 * If `NULL`, flushing will not be called automatically 
	 * and it's necessary to call `$batchModel->Flush()` manually.
	 * @param  int|NULL $flushSize 
	 * @return \MvcCore\Ext\Models\Db\IBatch
	 */
	public function SetAutoFlushSize ($flushSize);
	
	/**
	 * Get automatic batch flush size. Default value is 10 items. 
	 * If `NULL`, flushing is not called automatically 
	 * and it's necessary to call `$batchModel->Flush()` manually.
	 * @return int|NULL
	 */
	public function GetAutoFlushSize ();
	
	/**
	 * Get current count of model instances in batch.
	 * @return int
	 */
	public function GetSize () ;

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
	 * Return affected rows for all queries in last flush call.
	 * @return int
	 */
	public function GetAllResultsRowsCount ();
}