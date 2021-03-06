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

class		Connection 
implements	\MvcCore\Model\IConstants,
			\MvcCore\Ext\Models\Db\IConnection {

	/**
	 * `\PDO` connection provider instance.
	 * @var \PDO
	 */
	protected $provider = NULL;

	/**
	 * PDO driver specific connection string (it may contains crendentials in come cases).
	 * @var string
	 */
	protected $dsn;

	/**
	 * Connection user name, always not used in file databases without authentication.
	 * @var string
	 */
	protected $username;

	/**
	 * Database password, it's stored in memory all the time for reconnection purposes.
	 * @var string
	 */
	protected $password;

	/**
	 * `\PDO` constructor connection options.
	 * @var array
	 */
	protected $options;

	/**
	 * `TRUE` for multi statements connection type.
	 * @see https://stackoverflow.com/questions/38305108/disable-multiple-statements-in-php-pdo
	 * @var bool
	 */
	protected $multiStatements = FALSE;
	
	/**
	 *  Database server version in "PHP-standardized" version number string.
	 * @var string|NULL
	 */
	protected $version = NULL;

	/**
	 * Boolean about if current connection is inside transaction or not.
	 * @var bool
	 */
	protected $inTransaction = FALSE;
	
	/**
	 * If current connection is inside transaction, this could contains 
	 * current transaction name, else it's `NULL`.
	 * @var string
	 */
	protected $transactionName = NULL;

	/**
	 * Retry attemps total count to reconnect by system configuration (if implemented).
	 * @var int|NULL
	 */
	protected $retryAttemptsTotal = NULL;

	/**
	 * Retry attemps counter.
	 * @var int
	 */
	protected $retryAttempts = 0;

	/**
	 * Delay to wait before next reconnect in seconds, it coulds contain
	 * miliseconds after decimal point.
	 * @var float
	 */
	protected $retryDelay = 0;
	

	/**
	 * @inheritDocs
	 * @return array
	 */
	public static function GetAvailableDrivers () {
		return \PDO::getAvailableDrivers();
	}

	/**
	 * Creates a PDO instance representing a connection to a database.
	 * @param  string $dsn
	 * @param  string $username
	 * @param  string $password
	 * @param  array  $options
	 * @throws \Throwable
	 */
	public function __construct ($dsn, $username = NULL, $password = NULL, array $options = []) {
		$this->dsn = $dsn;
		$this->username = $username;
		$this->password = $password;
		$this->options = $options;
		
		if ($this->retryAttemptsTotal === NULL) {
			$this->retryAttemptsTotal = 0;
			$sysCfg = \MvcCore\Config::GetSystem();
			if ($sysCfg !== NULL) {
				$sysCfgProps = \MvcCore\Model::GetSysConfigProperties();
				$sysCfgDbSection = $sysCfg->{$sysCfgProps->sectionName};
				if ($sysCfgDbSection !== NULL) {
					if (isset($sysCfgDbSection->{$sysCfgProps->retryAttempts}))
						$this->retryAttemptsTotal = $sysCfgDbSection->{$sysCfgProps->retryAttempts};
					if (isset($sysCfgDbSection->{$sysCfgProps->retryDelay}))
						$this->retryDelay = floatval($sysCfgDbSection->{$sysCfgProps->retryDelay});
				}
			}
		}
		
		try {
			$this->connect();
		} catch (\Exception $e) { // backward compatibility
			$this->reConnectIfNecessaryOrThrownError($e);
		} catch (\Throwable $e) {
			$this->reConnectIfNecessaryOrThrownError($e);
		}
	}

	/**
	 * Return array of all instance or static local properties,
	 * where `\PDO` is replaced with empty instance.
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
			$result[$propName] = $propName === 'provider'
				? (new \ReflectionClass('\\PDO'))->newInstanceWithoutConstructor()
				: $prop->getValue($this);
		}
		return $result;
	}
	
	/**
	 * @inheritDocs
	 * @param  string|\string[] $statement
	 * @param  array            $driverOptions
	 * @throws \Throwable
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public function Prepare ($sql, $driverOptions = [\MvcCore\Ext\Models\Db\IStatement::AUTO_CLOSE]) {
		$sqlCode = is_array($sql) ? implode(" \n", $sql) : $sql;
		return $this->providerInvoke('prepare', [$sqlCode, $driverOptions], FALSE, FALSE);
	}

	/**
	 * @inheritDocs
	 * @param  string|\string[] $sql
	 * @throws \Throwable
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public function Query ($sql, $connectionIndexOrName = NULL) {
		$sqlCode = is_array($sql) ? implode(" \n", $sql) : $sql;
		return $this->providerInvoke('query', [$sqlCode, [\MvcCore\Ext\Models\Db\IStatement::AUTO_CLOSE]], TRUE, FALSE);
	}
	
	/**
	 * @inheritDocs
	 * @param  string|\string[] $sql
	 * @throws \Throwable
	 * @return \MvcCore\Ext\Models\Db\Readers\Execution
	 */
	public function Execute ($sql, $connectionIndexOrName = NULL) {
		$sqlCode = is_array($sql) ? implode(" \n", $sql) : $sql;
		return $this->providerInvoke('exec', [$sqlCode, [\MvcCore\Ext\Models\Db\IStatement::AUTO_CLOSE]], TRUE, TRUE);
	}



	/**
	 * @inheritDocs
	 * @param  string|NULL $sequenceName
	 * @param  string|NULL $targetType
	 * @return int|float|string|NULL
	 */
	public function LastInsertId ($sequenceName = NULL, $targetType = NULL) {
		$result = $this->provider->lastInsertId($sequenceName);
		if ($result !== NULL && $targetType !== NULL)
			settype($result, $targetType);
		return $result;
	}
	
	/**
	 * @inheritDocs
	 * @param  string $string
	 * @param  int    $paramType
	 * @return string
	 */
	public function Quote ($string , $paramType = \PDO::PARAM_STR) {
		return $this->provider->quote($string, $paramType);
	}
	
	/**
	 * @inheritDocs
	 * @param  string $identifierName
	 * @return string
	 */
	public function QuoteName ($identifierName) {
		if (mb_substr($identifierName, 0, 1) !== "'" && mb_substr($identifierName, -1, 1) !== "'") {
			if (mb_strpos($identifierName, '.') !== FALSE) 
				return "'".str_replace('.', "'.'", $identifierName)."'";
			return "'".$identifierName."'";
		}
		return $identifierName;
	}



	/**
	 * @inheritDocs
	 * @param  int $attribute
	 * @return mixed
	 */
	public function GetAttribute ($attribute) {
		return $this->provider->getAttribute($attribute);
	}

	/**
	 * @inheritDocs
	 * @param  int   $attribute
	 * @param  mixed $value
	 * @return bool
	 */
	public function SetAttribute ($attribute, $value) {
		return $this->provider->setAttribute($attribute , $value);
	}

	/**
	 * @inheritDocs
	 * @return null|string
	 */
	public function GetVersion () {
		return $this->version;
	}

	/**
	 * @inheritDocs
	 * @return bool|null
	 */
	public function IsMutliStatements () {
		return $this->mutliStatements;
	}

	/**
	 * @inheritDocs
	 * @return \PDO
	 */
	public function GetProvider () {
		return $this->provider;
	}

	/**
	 * @inheritDocs
	 * @return array
	 */
	public function GetConfig () {
		return [
			'dsn'		=> $this->dsn,
			'username'	=> $this->username,
			'password'	=> $this->password,
			'options'	=> $this->options,
		];
	}
	
	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function InTransaction () {
		return $this->inTransaction;
	}

	/**
	 * @inheritDocs
	 * @param  int    $flags
	 * @param  string $name
	 * @return bool
	 */
	public function BeginTransaction ($flags = 0, $name = NULL) {
		if ($name !== NULL) 
			$this->transactionName = $name;
		$this->inTransaction = TRUE;
		return $this->provider->beginTransaction();
	}

	/**
	 * @inheritDocs
	 * @param  int $flags
	 * @return bool
	 */
	public function Commit ($flags = 0) {
		$result = $this->provider->commit();
		$this->inTransaction = FALSE;
		$this->transactionName = NULL;
		return $result;
	}

	/**
	 * @inheritDocs
	 * @param  int $flags
	 * @return bool
	 */
	public function RollBack ($flags = 0) {
		$result = $this->provider->rollBack();
		$this->inTransaction = FALSE;
		$this->transactionName = NULL;
		return $result;
	}

	/**
	 * Closes the connection by unseting the `\PDO` provider instance.
	 * @return void
	 */
	protected function close () {
		$this->provider = NULL;
	}

	/**
	 * Connect into database with `\PDO` provider with possibly configured retries.
	 * @return \PDO
	 */
	protected function connect () {
		$this->provider = new \PDO(
			$this->dsn, $this->username, $this->password, $this->options
		);
		$this->setUpConnectionSpecifics();
		return $this->provider;
	}

	/**
	 * Set up connection specific properties depends on this driver.
	 * @return void
	 */
	protected function setUpConnectionSpecifics () {
		$serverVersionConst = '\PDO::ATTR_SERVER_VERSION';
		$serverVersionConstVal = defined($serverVersionConst) 
			? constant($serverVersionConst) 
			: 0;
		$this->version = $this->provider->getAttribute($serverVersionConstVal);
	}
	
	/**
	 * Check if given exception is about connection lost.
	 * @param  \Throwable $e 
	 * @return bool
	 */
	protected function isConnectionLost (\Throwable $e) {
		return FALSE;
	}

	/**
	 * Try to invoke methods `prepare()` or `query()` or `exec()` on internal `\PDO` 
	 * provider instance and if there has been any exception or any error thrown with 
	 * message like: `... server has gone away ...`, try to reconnect from PHP and try 
	 * to process given method with arguments again by configured retry count.
	 * @param  string $method
	 * @param  array  $args
	 * @param  bool   $executeProvider
	 * @param  bool   $returnReader
	 * @throws \Throwable
	 * @return \MvcCore\Ext\Models\Db\Statement|\MvcCore\Ext\Models\Db\Readers\Execution
	 */
	protected function providerInvoke (
		$method, $args, $executeProvider = FALSE, $returnReader = FALSE
	) {
		/**
		 * @var $exception \Throwable|NULL
		 * @var $dbErrorMsg string
		 * @var $providerResult \PDOStatement|int|NULL
		 */
		$exception = NULL;
		$dbErrorMsg = NULL;

		list($query, $driverOptions) = $args;
		
		if (($driverOptionsIndex = array_search(\MvcCore\Ext\Models\Db\IStatement::AUTO_CLOSE, $driverOptions)) !== FALSE) 
			unset($driverOptions[$driverOptionsIndex]);
		if (($driverOptionsIndex = array_search(\MvcCore\Ext\Models\Db\IStatement::DO_NOT_AUTO_CLOSE, $driverOptions)) !== FALSE) 
			unset($driverOptions[$driverOptionsIndex]);

		try {
			set_error_handler(function ($phpErrLevel, $errMessage) use (& $dbErrorMsg) {
				// $phpErrLevel is always with value `2` as warning
				$dbErrorMsg = $errMessage;
			});

			$providerResult = call_user_func_array(
				[$this->provider, $method], 
				[$query, $driverOptions]
			);

			restore_error_handler();
			if ($providerResult === FALSE) {
				$errInfo = $this->statement->errorInfo();
				throw new \Exception($errInfo[2] ?: $dbErrorMsg, intval($errInfo[0]));
			}
		} catch (\Exception $e) { // backward compatibility
			$exception = \MvcCore\Ext\Models\Db\Exception::Create($e)
				->setQuery($query);
			$providerResult = NULL;
		} catch (\Throwable $e) {
			$exception = \MvcCore\Ext\Models\Db\Exception::Create($e)
				->setQuery($query);
			$providerResult = NULL;
		}

		if ($providerResult === NULL) {
			$this->reConnectIfNecessaryOrThrownError($exception); // throws \Throwable
			return $this->providerInvoke($method, $args, $executeProvider, $returnReader);
		}
		
		$statement = new \MvcCore\Ext\Models\Db\Statement(
			$this, $providerResult, $args[1]
		);

		if ($executeProvider && $returnReader) 
			return $statement->Execute();
		if ($executeProvider) 
			$statement->Execute();
		return $statement;
	}

	/**
	 * Log given exception and print query with params on development.
	 * @param  \Throwable $error 
	 * @throws \Throwable 
	 */
	protected function handleError (\Throwable $error) {
		$isDev = \MvcCore\Application::GetInstance()->GetEnvironment()->IsDevelopment();
		if ($isDev && $error instanceof \MvcCore\Ext\Models\Db\Exception) {
			$query = $error->getQuery();
			$params = array_merge([], $error->getParams() ?: []);
			if (count($params) === 0) {
				\MvcCore\Debug::BarDump($query, 'Query:', [
					'truncate'	=> mb_strlen($query)
				]);
			} else {
				list(
					$dumpSuccess, $queryWithValues
				) = $this->devDumpQueryWithParams($query, $params);
				if ($dumpSuccess) {
					\MvcCore\Debug::BarDump($queryWithValues, 'Query with params:', [
						'truncate'	=> mb_strlen($queryWithValues)
					]);
				} else {
					\MvcCore\Debug::BarDump($query, 'Query:', [
						'truncate'	=> mb_strlen($query)
					]);
					\MvcCore\Debug::BarDump($params, 'Params:');
				}
			}
			\MvcCore\Debug::BarDump($this, 'Connection:');
			throw $error->getPrevious();
		} else {
			throw $error;
		}
	}

	/**
	 * Replace all params in query to dump query with values on development env.
	 * Return array with success boolean and replaced query.
	 * @param  string $query 
	 * @param  array  $params 
	 * @return array
	 */
	protected function devDumpQueryWithParams ($query, $params) {
		$paramsCnt = count($params);
		$assocParams = (
			array_keys($params) !== range(0, $paramsCnt - 1)
		);
		$prov = & $this->provider;
		array_walk($params, function (& $value, $key) use (& $prov) {
			if ($value === NULL) {
				$value = 'NULL';
			} else if (is_string($value)) {
				$value = $prov->quote($value, \PDO::PARAM_STR);
			}
		});
		if ($assocParams) {
			$resultItems = [];
			$matchesCount = 0;
			$resultQuery = " {$query} ";
			foreach ($params as $paramKey => $paramValue) {
				preg_match_all(
					"#([\s\(\)\!\=\>\<])({$paramKey})([\s\(\)\!\=\>\<])#", 
					$resultQuery, $matches, PREG_OFFSET_CAPTURE
				);
				if (count($matches) > 0 && count($matches[2]) === 1) {
					$matchIndex = $matches[2][0][1];
					$resultQuery = (
						mb_substr($resultQuery, 0, $matchIndex)
						. $paramValue
						. mb_substr($resultQuery, $matchIndex + mb_strlen($paramKey))
					);
					$matchesCount += 1;
				} else {
					break;
				}
			}
			if ($matchesCount === $paramsCnt) {
				$dumpSuccess = TRUE;
			} else {
				$resultQuery = $query;
				$dumpSuccess = FALSE;
			}
		} else {
			$dumpSuccess = FALSE;
			$resultQuery = $query;
			preg_match_all("#([^-a-zA-Z0-9_])(\?)([^-a-zA-Z0-9_])#", $query, $matches, PREG_OFFSET_CAPTURE);
			if (count($matches)) {
				$matchesQm = $matches[2];
				$matchesCnt = count($matchesQm);
				if ($matchesCnt === $paramsCnt) {
					$index = 0;
					$resultItems = [];
					foreach ($matchesQm as $key => $qmAndIndex) {
						$matchIndex = $qmAndIndex[1];
						$resultItems[] = mb_substr($query, $index, $matchIndex);
						$resultItems[] = $params[$key];
						$index = $matchIndex + 1;
					}
					if ($index < mb_strlen($query)) 
						$resultItems[] = mb_substr($query, $index);
					$resultQuery = implode('', $resultItems);
					$dumpSuccess = TRUE;
				}
			}
		}
		return [$dumpSuccess, $resultQuery];
	}

	/**
	 * Try to reconnect, if connection has been lost.
	 * @param  \Throwable $e
	 * @throws \Throwable
	 * @return \PDO|NULL
	 */
	protected function reConnectIfNecessaryOrThrownError (\Throwable $e) {
		if (
			$this->isConnectionLost($e) &&
			$this->retryAttempts < $this->retryAttemptsTotal
		) {
			$this->provider = NULL;
			$this->retryAttempts += 1;
			if ($this->retryDelay > 0.0) 
				usleep($this->retryDelay * 1000000);
			try {
				$this->connect();
			} catch (\Exception $e) { // backward compatibility
				$this->reConnectIfNecessaryOrThrownError($e);
			} catch (\Throwable $e) {
				$this->reConnectIfNecessaryOrThrownError($e);
			}
			return $this->provider;
		} else {
			$this->handleError($e); // throws \Throwable
			return NULL;
		}
	}
}