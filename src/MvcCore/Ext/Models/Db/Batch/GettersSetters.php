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
 */
trait GettersSetters {
	
	/**
	 * @inheritDocs
	 * @return int|NULL
	 */
	public function GetAutoFlushSize () {
		return $this->flushSize;
	}
	
	/**
	 * @inheritDocs
	 * @param  int|NULL $flushSize 
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function SetAutoFlushSize ($flushSize) {
		/** @var \MvcCore\Ext\Models\Db\Batch $this */
		$flushSizeIsInt = is_int($flushSize);
		if ($flushSizeIsInt && $flushSize > 0) {
			$this->autoFlush = TRUE;
			$this->flushSize = $flushSize;
		} else {
			$this->autoFlush = FALSE;
			$flushSizeAbs = $flushSizeIsInt ? abs($flushSize) : 0;
			if ($flushSizeAbs > 0)
				$this->flushSize = $flushSizeAbs;

		}
		return $this;
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
	 * @return \MvcCore\Ext\Models\Db\Model[]
	 */
	public function GetInstances () {
		return $this->instances;
	}
	
	/**
	 * @inheritDocs
	 * @return int
	 */
	public function GetRowsCount () {
		return $this->rowsCount;
	}

	/**
	 * @inheritDocs
	 * @param  string|int|NULL $connection 
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function SetConnectionName ($connectionName = NULL) {
		/** @var \MvcCore\Ext\Models\Db\Batch $this */
		$this->connectionName = $connectionName;
		return $this;
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
		/** @var \MvcCore\Ext\Models\Db\Batch $this */
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
}