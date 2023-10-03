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

class Reader implements \MvcCore\Ext\Models\Db\IReader {

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
	 * Metadata with `AffectedRows` and `LastInsertId` if driver doesn't support it.
	 * @var array
	 */
	protected $metaData = NULL;



	/**
	 * Internal constructor to create reader wrapper instance.
	 * @param \MvcCore\Ext\Models\Db\Statement $statement 
	 */
	public function __construct (\MvcCore\Ext\Models\Db\Statement $statement) {
		$this->statement = $statement;
		$this->stmntOpenedProp = new \ReflectionProperty($statement, 'opened');
		$this->stmntOpenedProp->setAccessible(TRUE);
	}

	/**
	 * @inheritDoc
	 * @throws \PDOException|\Throwable
	 * @return bool
	 */
	public function GetExecResult () {
		if ($this->providerExecResult === NULL) 
			$this->providerInvokeExecute();
		return $this->providerExecResult;
	}

	/**
	 * @inheritDoc
	 * @param  bool|NULL $execResult
	 * @return \MvcCore\Ext\Models\Db\Reader
	 */
	public function SetExecResult ($execResult) {
		$this->providerExecResult = $execResult;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return array|NULL
	 */
	public function GetRawData () {
		return $this->rawData;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public function GetStatement () {
		return $this->statement;
	}


	/**
	 * Execute provider statement if necessary and 
	 * fetch single or multiple rows into result variable,
	 * close cursor and return the result.
	 * @param  bool $singleRow 
	 * @param  int  $fetchMode 
	 * @throws \PDOException|\Throwable
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
	 * @throws \PDOException|\Throwable
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
		
		$connection = $this->statement->GetConnection();
		$debugger = $connection->GetDebugger();
		$debugging = $debugger !== NULL;

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

			// transcode params with string values if necessary:
			if (!$connection->GetTranscode()) {
				$transcodedParams = $params;
			} else {
				$transcodedParams = [];
				$transcodingCharsets = $connection->GetTranscodingCharsets();
				$clientCharset = $transcodingCharsets->client;
				$databaseCharset = $transcodingCharsets->database . '//TRANSLIT//IGNORE';
				foreach ($params as $paramName => $paramValue) {
					if (!is_string($paramValue)) {
						$transcodedParamValue = $paramValue;
					} else {
						$transcodedParamValue = iconv($clientCharset, $databaseCharset, $paramValue);
						if ($transcodedParamValue === FALSE)
							$transcodedParamValue = $paramValue;
					}
					$transcodedParams[$paramName] = $transcodedParamValue;
				}
			}

			if ($debugging) $reqTime = microtime(TRUE);

			$this->providerExecResult = $providerStatement->execute($transcodedParams);

			if ($debugging) 
				$debugger->AddQuery(
					$providerStatement->queryString, $params, $reqTime, microtime(TRUE), $connection
				);

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
	 * Get SQL command first word in lower case.
	 * @param  string $sql 
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

	/**
	 * Execute provider specific statement to get previous statement metadata.
	 * @param  \MvcCore\Ext\Models\Db\Connection $connection 
	 * @param  string $metaStatement 
	 * @throws \PDOException|\Throwable
	 * @return array|NULL
	 */
	protected function getMetaData (\MvcCore\Ext\Models\Db\IConnection $connection, $metaStatement) {
		if ($this->metaData === NULL) {
			$this->metaData = $connection
				->Prepare($metaStatement)
				->FetchOne()
				->ToArray();
		}
		return $this->metaData;
	}
}