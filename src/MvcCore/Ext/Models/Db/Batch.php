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

	use \MvcCore\Ext\Models\Db\Model\Manipulation;

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
	 * Operations to execute on collected instances.
	 * @var \int[]
	 */
	protected $operations = [];

	/**
	 * Affected rows for all queries in last flush call.
	 * @var int
	 */
	protected $allResultsRowsCount = 0;

	
	/**
	 * 
	 * @param  int|NULL $flushSize 
	 * @return \MvcCore\Ext\Models\Db\IBatch
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
	 * @param  int                          $operation 
	 * @throws \InvalidArgumentException    Model instance is already in batch.
	 * @throws \Exception                   Any exception thrown by `Flush()` method.
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function AddToBatch (\MvcCore\Ext\Models\Db\IModel $modelInstance, $operation = \MvcCore\Ext\Models\Db\IBatch::OPERATION_SAVE) {
		if (in_array($modelInstance, $this->instances, TRUE)) 
			throw new \InvalidArgumentException(
				"[".get_class($this)."] Model instance is already in batch."
			);
		$this->instances[] = $modelInstance;
		$this->operations[] = $operation;
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
		$sql = [];
		$params = [];
		foreach ($this->instances as $index => $instance) {
			$operation = $this->operations[$index];
			if ($operation === \MvcCore\Ext\Models\Db\IBatch::OPERATION_SAVE) {
				static::editSave($instance, NULL, 0, static::getEditMetaDataCollections(0));
			} else if ($operation === \MvcCore\Ext\Models\Db\IBatch::OPERATION_INSERT) {
				static::editInsert($instance, 0, static::getEditMetaDataCollections(0));
			} else if ($operation === \MvcCore\Ext\Models\Db\IBatch::OPERATION_UPDATE) {
				static::editUpdate($instance, 0, static::getEditMetaDataCollections(0));
			} else if ($operation === \MvcCore\Ext\Models\Db\IBatch::OPERATION_DELETE) {
				static::editDelete($instance, 0, static::getEditMetaDataCollections(0));
			}
		}
		$this->allResultsRowsCount = self::GetConnection()
			->Prepare($sql)
			->Execute($params)
			->GetAllResultsRowsCount();
		$this->instances = [];
		$this->operations = [];
		$this->size = 0;
		return $this;
	}
	
	/**
	 * @inheritDocs
	 * @return int
	 */
	public function GetAllResultsRowsCount () {
		return $this->allResultsRowsCount;
	}


	protected static function getEditProviderResource () {
		$stop();
		return new \MvcCore\Ext\Models\Db\Providers\Resource;
	}
}