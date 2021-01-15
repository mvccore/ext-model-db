<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Ext\Models\Db;

class Reader implements \MvcCore\Ext\Models\Db\IReader
{
	/**
	 * Statement wrapper object.
	 * @var \MvcCore\Ext\Models\Db\Statement
	 */
	protected $statement;

	/**
	 * `\PDOstatement::execute()` result value.
	 * @var bool|NULL
	 */
	protected $providerExecResult = NULL;
	
	/** 
	 * All fetched rows or single row. Not used for stream reader.
	 * @var mixed
	 */
	protected $rawData = NULL;
	
	/**
	 * Internal reflection property to set `$this->reader->opened` protected property.
	 * @var \ReflectionProperty
	 */
	protected $stmntOpenedProp = NULL;



	/**
	 * Internal constructor to create reader wrapper instance.
	 * @param Statement $statement 
	 */
	public function __construct (\MvcCore\Ext\Models\Db\Statement $statement) {
		$this->statement = $statement;
		$this->stmntOpenedProp = new \ReflectionProperty($statement, 'opened');
		$this->stmntOpenedProp->setAccessible(TRUE);
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function GetExecResult () {
		if ($this->providerExecResult === NULL) 
			$this->providerInvokeExecute();
		return $this->providerExecResult;
	}

	/**
	 * @inheritDocs
	 * @return array
	 */
	public function GetRawData () {
		return $this->rawData;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public function GetStatement () {
		return $this->statement;
	}



	/**
	 * Execute provider statement if necessary and 
	 * fetch single or multiple rows into result variable,
	 * close cursor and return the result.
	 * @param bool $singleRow 
	 * @param int $fetchMode 
	 * @return array|NULL
	 */
	protected function & fetchRawData ($singleRow, $fetchMode = \PDO::FETCH_ASSOC) {
		if ($this->providerExecResult === NULL && $this->statement->IsOpened() !== FALSE)
			$this->providerInvokeExecute();
		$statement = $this->statement->GetProviderStatement();
		if ($singleRow) {
			$this->rawData = $statement->fetch($fetchMode);
		} else {
			$this->rawData = $statement->fetchAll($fetchMode);
		}
		$autoCloseStatement = in_array(
			\MvcCore\Ext\Models\Db\IStatement::AUTO_CLOSE, 
			$this->statement->GetDriverOptions()
		);
		if ($autoCloseStatement) 
			$this->statement->Close();
		return $this->rawData;
	}

	/**
	 * Execute prepared statement. If connection is lost, reconnect and 
	 * if query is select, try to prepare it again and execute it again.
	 * @throws \Exception 
	 * @throws \PDOException 
	 * @return \MvcCore\Ext\Models\Db\Reader
	 */
	protected function providerInvokeExecute () {
		/*
		$testConn = $this->statement->GetConnection();
		$testConnType = new \ReflectionClass($testConn);
		$testProp = $testConnType->getProperty('retryAttempts');
		$testProp->setAccessible(TRUE);
		$testRetryAttempts = $testProp->getValue($testConn);
		*/
		if ($this->providerExecResult !== NULL) return $this;

		$params = $this->statement->GetParams();
		$providerStatement = $this->statement->GetProviderStatement();
		/**
		 * @var $exception \Throwable|NULL
		 * @var $dbErrorMsg string
		 */
		$exception = NULL;
		$dbErrorMsg = NULL;
		try {
			set_error_handler(function ($phpErrLevel, $errMessage) use (& $dbErrorMsg) {
				// $phpErrLevel is always with value `2` as warning
				$dbErrorMsg = $errMessage;
			});
			
			$this->providerExecResult = $providerStatement->execute($params);
			$this->stmntOpenedProp->setValue($this->statement, TRUE);
			
			restore_error_handler();
			if (!$this->providerExecResult) {
				$errInfo = $providerStatement->errorInfo();
				throw new \Exception($errInfo[2] ?: $dbErrorMsg, intval($errInfo[0]));
			}

			/*
			if ($testRetryAttempts === 0)
				throw new \PDOException(" server has gone away ");
			*/
		} catch (\Throwable $e) {
			$exception = \MvcCore\Ext\Models\Db\Exception::Create($e)
				->setQuery($providerStatement->queryString)
				->setParams($params);
			$this->providerExecResult = FALSE;
		}
		
		if ($this->providerExecResult === FALSE) {
			
			$connection = $this->statement->GetConnection();
			$testConnType = new \ReflectionClass($connection);
			$retryConnectMethod = $testConnType->getMethod('reConnectIfNecessaryOrThrownError');
			$retryConnectMethod->setAccessible(TRUE);
			
			$providerConn = $retryConnectMethod->invokeArgs(
				$connection, [$exception]
			);

			$firstWord = $this->getSqlCommandFirstWord($providerStatement->queryString);
			if ($firstWord === 'select') {
				$providerStatement = $providerConn->prepare($providerStatement->queryString . ';');
				$statementType = new \ReflectionClass($this->statement);
				$provStatementProp = $statementType->getProperty('providerStatement');
				$provStatementProp->setAccessible(TRUE);
				$provStatementProp->setValue($this->statement, $providerStatement);
				return $this->providerInvokeExecute();
			}
		}
		return $this;
	}

	/**
	 * Clean up property `rawData` after reading is finished.
	 * This function is not used in stream reader.
	 * @return void
	 */
	protected function cleanUpData () {
		$this->rawData = NULL;
	}

	/**
	 * Get SQL command first word in lower case.
	 * @param string $sql 
	 * @return string|NULL
	 */
	protected function getSqlCommandFirstWord ($sql) {
		$sqlTrimmed = trim($sql, "; \t\n\r\0\x0B");
		preg_match("#\s#", $sqlTrimmed, $matches, PREG_OFFSET_CAPTURE);
		if ($matches && $matches[0]) {
			$firstWhiteSpacePos = $matches[0][1];
			$firstWord = mb_strtolower(mb_substr($sqlTrimmed, 0, $firstWhiteSpacePos));
			return $firstWord;
		}
		return $sql;
	}
}