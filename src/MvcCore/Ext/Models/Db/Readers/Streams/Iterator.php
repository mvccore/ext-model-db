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

namespace MvcCore\Ext\Models\Db\Readers\Streams;

class		Iterator 
implements	\MvcCore\Ext\Models\Db\Readers\Streams\IIterator,
			\Iterator,
			\Countable {

	#region Properties

	/**
	 * Stream reader object.
	 * @var \MvcCore\Ext\Models\Db\Readers\Stream|\MvcCore\Ext\Models\Db\Readers\IStream
	 */
	protected $reader;
	
	/**
	 * Statement object.
	 * @var \PDOStatement
	 */
	protected $providerStatement;

	/**
	 * Stream reader execute method to rewind iterator.
	 * @var \ReflectionMethod
	 */
	protected $executeMethod;

	/**
	 * Row completer method to complete result item.
	 * @var string
	 */
	protected $completerMethod;

	/**
	 * Completer local properties prepared once in constructor.
	 * @var \stdClass
	 */
	protected $completerProps;
	
	/**
	 * Internal iterator index.
	 * @var int|NULL
	 */
	protected $index = NULL;
	
	/**
	 * Internal boolean about to continue in loop execution.
	 * @var bool
	 */
	protected $valid = FALSE;
	
	/**
	 * Result loop key from completer method.
	 * @var int|float|string|bool|NULL
	 */
	protected $resultKey = NULL;

	/**
	 * Result loop value from completer method.
	 * @var mixed
	 */
	protected $resultValue = NULL;
	
	#endregion Properties

	
	#region Public methods

	/**
	 * Internal constructor to create stream iterator.
	 * @param \MvcCore\Ext\Models\Db\Readers\Stream $reader
	 * @param string                                $completerName
	 * @param array                                 $completerArguments
	 */
	public function __construct (\MvcCore\Ext\Models\Db\Readers\IStream $reader, $completerName, $completerArguments) {
		// Store provider statement object into current context to fetch rows from database:
		$this->providerStatement = $reader->GetStatement()->GetProviderStatement();
		// Store reader and it's execution method to restart iterator again if necessary:
		$this->reader = $reader;
		$this->executeMethod = new \ReflectionMethod($reader, 'providerInvokeExecute');
		$this->executeMethod->setAccessible(TRUE);
		// Store current class completer method to call result value completing for each row:
		$this->completerMethod = 'to' . ucfirst($completerName);
		// Prepare row completer internal properties once:
		$propsPreparingHandler = 'prepare' . ucfirst($completerName);
		$this->{$propsPreparingHandler}($completerArguments);
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Ext\Models\Db\Readers\Stream
	 */
	public function GetReader () {
		return $this->reader;
	}
	
	/**
	 * @inheritDoc
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public function GetStatement () {
		return $this->reader->GetStatement();
	}
	
	/**
	 * @inheritDoc
	 * @return \MvcCore\Ext\Models\Db\Connection
	 */
	public function GetConnection () {
		return $this->reader->GetStatement()->GetConnection();
	}
	
	/**
	 * @inheritDoc
	 * @return void
	 */
	public function Close () {
		// Close database cursor.
		$this->GetStatement()->Close();
		// Unset and reset internal values.
		$this->valid = FALSE;
		$this->resultKey = NULL;
		$this->resultValue = NULL;
	}

	/**
	 * Return array of all instance or static local properties,
	 * where `\PDOStatement` is replaced with simple array.
	 * @return array
	 */
	public function __debugInfo () {
		$connType = new \ReflectionClass($this);
		$props = $connType->getProperties(
			\ReflectionProperty::IS_PRIVATE |
			\ReflectionProperty::IS_PROTECTED |
			\ReflectionProperty::IS_PUBLIC |
			\ReflectionProperty::IS_STATIC
		);
		$result = [];
		foreach ($props as $prop) {
			if (!$prop->isPublic()) 
				$prop->setAccessible(TRUE);
			$propName = $prop->getName();
			$result[$propName] = $propName === 'providerStatement'
				? get_object_vars($this->providerStatement)
				: $prop->getValue($this);
		}
		return $result;
	}

	#endregion Public methods


	#region Protected methods - preparing

	/**
	 * Prepare instance completer internal properties.
	 * @param  array $completerArguments 
	 * @return void
	 */
	protected function prepareInstances (& $completerArguments) {
		list(
			$fullClassName, $readingFlags, $keyColumnName, $keyType, $conn, $transcode
		) = $completerArguments;
		$type = new \ReflectionClass($fullClassName);
		if (!$type->hasMethod('SetValues'))
			throw new \InvalidArgumentException(
				"[".get_class()."] Class `{$fullClassName}` has no public method ".
				"`SetValues (\$data = [], \$propsFlags = 0): \MvcCore\Model`."
			);
		$this->completerProps = (object) [
			'type'			=> $type,
			'readingFlags'	=> $readingFlags,
			'keyColumnName'	=> $keyColumnName,
			'keyType'		=> $keyType,
			'retypeKey'		=> $keyType !== NULL,
			'useRawKey'		=> $keyColumnName === NULL,
			'connection'	=> $conn,
			'transcode'		=> $transcode,
		];
	}
	
	/**
	 * Prepare array completer internal properties.
	 * @param  array $completerArguments 
	 * @return void
	 */
	protected function prepareArrays (& $completerArguments) {
		$this->prepareArraysAndObjects($completerArguments);
	}
	
	/**
	 * Prepare objects completer internal properties.
	 * @param  array $completerArguments 
	 * @return void
	 */
	protected function prepareObjects (& $completerArguments) {
		$this->prepareArraysAndObjects($completerArguments);
	}

	/**
	 * Prepare arrays or objects completer internal properties.
	 * @param  array $completerArguments 
	 * @return void
	 */
	protected function prepareArraysAndObjects (& $completerArguments) {
		list(
			$keyColumnName, $keyType, $conn, $transcode
		) = $completerArguments;
		$this->completerProps = (object) [
			'keyColumnName'	=> $keyColumnName,
			'keyType'		=> $keyType,
			'retypeKey'		=> $keyType !== NULL,
			'useRawKey'		=> $keyColumnName === NULL,
			'connection'	=> $conn,
			'transcode'		=> $transcode,
		];
	}
	
	/**
	 * Prepare scalars completer internal properties.
	 * @param  array $completerArguments 
	 * @return void
	 */
	protected function prepareScalars (& $completerArguments) {
		list(
			$valueColumnName, $valueType, $keyColumnName, $keyType, $conn, $transcode
		) = $completerArguments;
		$this->completerProps = (object) [
			'valueColumnName'	=> $valueColumnName,
			'valueType'			=> $valueType,
			'keyColumnName'		=> $keyColumnName,
			'keyType'			=> $keyType,
			'retypeKey'			=> $keyType !== NULL,
			'retypeValue'		=> $valueType !== NULL,
			'useRawKey'			=> $keyColumnName === NULL,
			'connection'		=> $conn,
			'transcode'			=> $transcode,
		];
	}
	
	/**
	 * Prepare any types completer internal properties.
	 * @param  array $completerArguments 
	 * @return void
	 */
	protected function prepareAny (& $completerArguments) {
		list(
			$valueCompleter, $keyColumnName, $keyType, $conn, $transcode
		) = $completerArguments;
		$this->completerProps = (object) [
			'valueCompleter'		=> $valueCompleter,
			'keyColumnName'			=> $keyColumnName,
			'keyType'				=> $keyType,
			'retypeKey'				=> $keyType !== NULL,
			'useRawKey'				=> $keyColumnName === NULL,
			'connection'			=> $conn,
			'transcode'				=> $transcode,
		];
	}
	
	#endregion Protected methods - preparing
	

	#region Protected methods - completing

	/**
	 * Instances result value completer.
	 * @param  int|float|string|bool $rawKey 
	 * @param  array                 $rawRow 
	 * @return void
	 */
	protected function toInstances ($rawKey, & $rawRow) {
		$props = $this->completerProps;
		
		$itemKey = $props->useRawKey
			? $rawKey
			: $rawRow[$props->keyColumnName];
		if ($props->retypeKey)
			settype($itemKey, $props->keyType);
		if ($props->transcode) {
			if (!$props->useRawKey && is_string($itemKey))
				$itemKey = $props->connection->TranscodeResultValue($itemKey);
			$itemValues = $props->connection->TranscodeResultRowValues($rawRow);
		} else {
			$itemValues = $rawRow;
		}
		/** @var \MvcCore\Ext\Models\Db\Model $item */
		$item = $props->type->newInstanceWithoutConstructor();
		$item->SetValues($itemValues, $props->readingFlags);
		
		$this->resultKey = $itemKey;
		$this->resultValue = $item;
	}
	
	/**
	 * Arrays result value completer.
	 * @param  int|float|string|bool $rawKey 
	 * @param  array                 $rawRow 
	 * @return void
	 */
	protected function toArrays ($rawKey, & $rawRow) {
		$props = $this->completerProps;
		
		$itemKey = $props->useRawKey
			? $rawKey 
			: $rawRow[$props->keyColumnName];
		if ($props->retypeKey)
			settype($itemKey, $props->keyType);
		if ($props->transcode) {
			if (!$props->useRawKey && is_string($itemKey))
				$itemKey = $props->connection->TranscodeResultValue($itemKey);
			$itemValue = $props->connection->TranscodeResultRowValues($rawRow);
		} else {
			$itemValue = & $rawRow;
		}

		$this->resultKey = $itemKey;
		$this->resultValue = & $itemValue;
	}
	
	/**
	 * Objects result value completer.
	 * @param  int|float|string|bool $rawKey 
	 * @param  array                 $rawRow 
	 * @return void
	 */
	protected function toObjects ($rawKey, & $rawRow) {
		$props = $this->completerProps;
		
		$itemKey = $props->useRawKey
			? $rawKey 
			: $rawRow[$props->keyColumnName];
		if ($props->retypeKey)
			settype($itemKey, $props->keyType);
		if ($props->transcode) {
			if (!$props->useRawKey && is_string($itemKey))
				$itemKey = $props->connection->TranscodeResultValue($itemKey);
			$itemValue = (object) $props->connection->TranscodeResultRowValues($rawRow);
		} else {
			$itemValue = (object) $rawRow;
		}

		$this->resultKey = $itemKey;
		$this->resultValue = $itemValue;
	}
	
	/**
	 * Scalars result value completer.
	 * @param  int|float|string|bool $rawKey 
	 * @param  array                 $rawRow 
	 * @return void
	 */
	protected function toScalars ($rawKey, & $rawRow) {
		$props = $this->completerProps;
		
		$itemKey = $props->useRawKey
			? $rawKey
			: $rawRow[$props->keyColumnName];
		if ($props->retypeKey)
			settype($itemKey, $props->keyType);
		$itemValue = array_key_exists($props->valueColumnName, $rawRow)
			? $rawRow[$props->valueColumnName]
			: NULL;
		if ($props->retypeValue)
			settype($itemValue, $props->valueType);
		if ($props->transcode) {
			if (!$props->useRawKey && is_string($itemKey)) 
				$itemKey = $props->connection->TranscodeResultValue($itemKey);
			if (is_string($itemValue)) 
				$itemValue = $props->connection->TranscodeResultValue($itemValue);
		}

		$this->resultKey = $itemKey;
		$this->resultValue = $itemValue;
	}
	
	/**
	 * Any types result value completer.
	 * @param  int|float|string|bool $rawKey 
	 * @param  array                 $rawRow 
	 * @return void
	 */
	protected function toAny ($rawKey, & $rawRow) {
		$props = $this->completerProps;
		$valueCompleter = $props->valueCompleter;
		
		$itemKey = $props->useRawKey
			? $rawKey
			: $rawRow[$props->keyColumnName];
		if ($props->retypeKey)
			settype($itemKey, $props->keyType);
		if ($props->transcode) {
			if (!$props->useRawKey && is_string($itemKey)) 
				$itemKey = $props->connection->TranscodeResultValue($itemKey);
			$itemValues = $props->connection->TranscodeResultRowValues($rawRow);
		} else {
			$itemValues = $rawRow;
		}
		
		$this->resultKey = $itemKey;
		$this->resultValue = $valueCompleter($itemValues, $itemKey);
	}
	
	#endregion Protected methods - completing


	#region \Iterator

	/**
	 * Called once before foreach loop execution.
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function rewind () {
		$statementIsOpened = $this->GetStatement()->IsOpened();
		if ($statementIsOpened === NULL && $this->index === NULL) {
			// If stream is not executed yet and index is on start position:
			// Execute reader for first time to iterate loop properly:
			$this->executeMethod->invoke($this->reader);
		} else if ($this->index !== NULL) {
			// If stream is rewind again - it could be opened or closed from previous loop execution.
			// Close any possible previous unclosed custor:
			$this->GetStatement()->Close();
			// Execute reader again to iterate loop again properly:
			$this->executeMethod->invoke($this->reader);
		}
		// Fetch first row:
		$fetchResult = $this->providerStatement->fetch(\PDO::FETCH_ASSOC);
		if ($fetchResult === FALSE) {
			$this->Close();
		} else {
			$this->valid = TRUE;
			$this->index = 0;
			$this->{$this->completerMethod}($this->index, $fetchResult);
		}
	}
	
	/**
	 * Called each foreach loop execution, returning foreach value.
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function current () {
		return $this->resultValue;
	}
	
	/**
	 * Called each foreach loop execution, returning foreach key.
	 * @return int|float|string|bool
	 */
	#[\ReturnTypeWillChange]
	public function key () {
		return $this->resultKey;
	}
	
	/**
	 * Called before each foreach loop execution to move internal values to next step.
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function next () {
		$fetchResult = $this->providerStatement->fetch(\PDO::FETCH_ASSOC);
		if ($fetchResult === FALSE) {
			$this->Close();
		} else {
			$this->index++;
			$this->{$this->completerMethod}($this->index, $fetchResult);
		}
	}
	
	/**
	 * Called before each foreach loop execution (after `next()` function) to continue in loop or not.
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function valid () {
		return $this->valid;
	}

	#endregion \Iterator


	#region \Countable

	/**
	 * The number of all items in the stream iterator.
	 * It is only available after the first iterator loop is executed.
	 * @throws \RuntimeException 
	 * @return int
	 */
	#[\ReturnTypeWillChange]
	public function count () {
		if ($this->index !== NULL && $this->valid === FALSE) 
			return $this->index + 1;
		throw new \RuntimeException(
			"The number of all items in the stream iterator is not ".
			"available before all items are iterated. The number ".
			"of iterator items is only available after the first ".
			"iterator loop is executed."
		);
	}

	#endregion \Countable
}