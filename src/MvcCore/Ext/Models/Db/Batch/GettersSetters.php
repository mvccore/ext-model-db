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
	 * @inheritDoc
	 * @return ?int
	 */
	public function GetAutoFlushSize () {
		return $this->flushSize;
	}
	
	/**
	 * @inheritDoc
	 * @param  ?int $flushSize 
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
	 * @inheritDoc
	 * @return int
	 */
	public function GetSize () {
		return $this->size;
	}
	
	/**
	 * @inheritDoc
	 * @return \MvcCore\Ext\Models\Db\Model[]
	 */
	public function GetInstances () {
		return $this->instances;
	}
	
	/**
	 * @inheritDoc
	 * @return int
	 */
	public function GetRowsCount () {
		return $this->rowsCount;
	}

	/**
	 * @inheritDoc
	 * @param  string|int|null $connection 
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function SetConnectionName ($connectionName = NULL) {
		/** @var \MvcCore\Ext\Models\Db\Batch $this */
		$this->connectionName = $connectionName;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Ext\Models\Db\Model $modelInstance 
	 * @return bool
	 */
	public function HasInBatch (\MvcCore\Ext\Models\Db\IModel $modelInstance) {
		return in_array($modelInstance, $this->instances, TRUE);
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Ext\Models\Db\Model $modelInstance 
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function RemoveFromBatch (\MvcCore\Ext\Models\Db\IModel $modelInstance) {
		/** @var \MvcCore\Ext\Models\Db\Batch $this */
		$instanceIndex = array_search($modelInstance, $this->instances, TRUE);
		if ($instanceIndex !== NULL) {
			array_splice($this->instances, $instanceIndex, 1);
			array_splice($this->operationsFlags, $instanceIndex, 1);
			$this->size -= 1;
		}
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @param  \MvcCore\Ext\Models\Db\Model $modelInstance 
	 * @param  int                          $operationFlags 
	 * @throws \InvalidArgumentException    Model instance is already in batch.
	 * @throws \Exception                   Any exception thrown by `Flush()` method.
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function AddToBatch (\MvcCore\Ext\Models\Db\IModel $modelInstance, $operationFlags = \MvcCore\Ext\Models\Db\IBatch::OPERATION_SAVE) {
		/** @var \MvcCore\Ext\Models\Db\Batch $this */
		if (in_array($modelInstance, $this->instances, TRUE)) {
			try {
				$modelInstanceStr = (string) $modelInstance;
				if ($modelInstanceStr === get_class($modelInstance))
					throw new \Exception($modelInstanceStr);
			} catch (\Throwable $e1) {
				try {
					$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
					$modelInstanceStr = $toolsClass::JsonEncode($modelInstance->GetValues());
				} catch (\Throwable $e2) {
					$modelInstanceStr = $e1->getMessage();
				}
			}
			throw new \InvalidArgumentException(
				"Model instance is already in batch ({$modelInstanceStr})."
			);
		}
		$this->instances[] = $modelInstance;
		$this->operationsFlags[] = $operationFlags;
		$this->size += 1;
		if ($this->autoFlush && $this->size === $this->flushSize)
			return $this->Flush();
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $instanceEditResourceType 
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function SetInstanceEditResourceType ($instanceEditResourceType) {
		/** @var \MvcCore\Ext\Models\Db\Batch $this */
		$type = new \ReflectionClass($instanceEditResourceType);
		if (!$type->implementsInterface(static::BATCH_INSTANCE_EDIT_RESOURCE_INTERFACE))
			throw new \RuntimeException(
				"Class `{$instanceEditResourceType}` doesn't implement "
				."interface `".static::BATCH_INSTANCE_EDIT_RESOURCE_INTERFACE."`."
			);
		$this->instanceEditResourceType = $type;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return \ReflectionClass
	 */
	public function GetInstanceEditResourceType () {
		/** @var \MvcCore\Ext\Models\Db\Batch $this */
		if ($this->instanceEditResourceType !== NULL)
			return $this->instanceEditResourceType;
		return $this->instanceEditResourceType = new \ReflectionClass(
			static::BATCH_INSTANCE_EDIT_RESOURCE_TYPE
		);
	}

	/**
	 * @inheritDoc
	 * @param  array $ctorArgs 
	 * @return \MvcCore\Ext\Models\Db\Batch
	 */
	public function SetInstanceEditResourceCtorArgs (array $ctorArgs) {
		/** @var \MvcCore\Ext\Models\Db\Batch $this */
		$this->instanceEditResourceCtorArgs = $ctorArgs;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return array
	 */
	public function GetInstanceEditResourceCtorArgs () {
		/** @var \MvcCore\Ext\Models\Db\Batch $this */
		return $this->instanceEditResourceCtorArgs;
	}

}