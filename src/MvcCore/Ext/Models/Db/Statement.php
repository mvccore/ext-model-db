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

use \MvcCore\Ext\Models\Db,
	\MvcCore\Ext\Models\Db\Readers;

class Statement implements \MvcCore\Ext\Models\Db\IStatement
{
	/**
	 * Database connection wrapper.
	 * @var \MvcCore\Ext\Models\Db\Connection
	 */
	protected $connection = NULL;

	/**
	 * Internal `\PDOStatement` object.
	 * @var \PDOStatement
	 */
	protected $providerStatement = NULL;

	/**
	 * `\PDO::prepare()` second argument (`$driver_options`) value.
	 * @var array
	 */
	protected $driverOptions = [];

	/**
	 * Statement execution params.
	 * @var array
	 */
	protected $params = NULL;

	/**
	 * Data reader wrapper, depends on exection call type.
	 * @var Db\Reader|Readers\Multiple|Readers\Stream|Readers\Single|Readers\Execution
	 */
	protected $reader = NULL;

	/**
	 * Statement opened boolean:
	 * - `NULL` if statement is prepared and not executed yet.
	 * - `TRUE` if statement is executed and if cursor is not closed yet.
	 * - `FALSE` if statement cursor is closed.
	 * @var bool|NULL
	 */
	protected $opened = NULL;

	
	/**
	 * @inheritDocs
	 * @param string|\string[] $sql 
	 * @param string|int|array|\stdClass|NULL $connectionNameOrConfig
	 * @param array $driverOptions
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public static function Prepare ($sql, $connectionNameOrConfig = NULL, $driverOptions = [\MvcCore\Ext\Models\Db\IStatement::AUTO_CLOSE]) {
		list(,$callerInfo) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		
		if (!isset($callerInfo['class']))
			throw new \RuntimeException(
				"Database static statement preparing has to be called from class only."
			);

		$fullClassName = '\\' . ltrim($callerInfo['class']);
		
		if ($connectionNameOrConfig === NULL) {
			
			$getMetaDataMethod = new \ReflectionMethod($fullClassName, 'getMetaData');
			$getMetaDataMethod->setAccessible(TRUE);
			list(/*$metaData*/, $connAttrArgs) = $getMetaDataMethod->invokeArgs(
				NULL, [0, [\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_CONNECTIONS]]
			);

			if ($connAttrArgs > 0) 
				$connectionNameOrConfig = $connAttrArgs[0];
		}
		
		$connection = $fullClassName::GetConnection($connectionNameOrConfig, TRUE);
		
		return $connection->Prepare($sql, $driverOptions ?: []);
	}



	/**
	 * Internal constructor to create statement wrapper instance.
	 * @param \MvcCore\Ext\Models\Db\Connection $connection 
	 * @param \PDOStatement $statement
	 * @param array $driverOptions
	 */
	public function __construct (
		\MvcCore\Ext\Models\Db\Connection $connection, 
		\PDOStatement $statement,
		array $driverOptions = []
	) {
		$this->connection = $connection;
		$this->providerStatement = $statement;
		$this->driverOptions = $driverOptions;
	}



	/**
	 * @inheritDocs
	 * @return \MvcCore\Ext\Models\Db\Connection
	 */
	public function GetConnection () {
		return $this->connection;
	}
	
	/**
	 * @inheritDocs
	 * @return \PDO
	 */
	public function GetProvider () {
		return $this->connection->GetProvider();
	}

	/**
	 * @inheritDocs
	 * @return \PDOStatement
	 */
	public function GetProviderStatement () {
		return $this->providerStatement;
	}
	
	/**
	 * @inheritDocs
	 * @return array
	 */
	public function GetDriverOptions () {
		return $this->driverOptions;
	}
	
	/**
	 * @inheritDocs
	 * @return array Query params array, it could be sequential or associative array. 
	 */
	public function & GetParams () {
		return $this->params;
	}
	
	/**
	 * @inheritDocs
	 * @param array $params Query params array, it could be sequential or associative array. 
	 *						This parameter can be used as an infinite argument for the function.
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public function SetParams ($params = []) {
		if (func_num_args() > 1 || (func_num_args() > 0 && !is_array(func_get_arg(0))))
			$params = func_get_args();
		if (is_array($params))
			$this->params = & $params;
		return $this;
	}
	
	/**
	 * @inheritDocs
	 * @throws \RuntimeException
	 * @return bool
	 */
	public function GetExecResult () {
		if ($this->reader === NULL)
			throw new \RuntimeException("Statement hasn't been executed yet.");
		return $this->reader->GetExecResult();
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function Close () {
		$this->opened = FALSE;
		return $this->providerStatement->closeCursor();
	}
	
	/**
	 * @inheritDocs
	 * @return bool|NULL
	 */
	public function IsOpened () {
		return $this->opened;
	}

	/**
	 * @inheritDocs
	 * @throws \RuntimeException
	 * @return bool
	 */
	public function NextResultSet () {
		if ($this->opened !== TRUE)
			throw new \RuntimeException("Statement hasn't been executed yet.");
		return $this->providerStatement->nextRowset();
	}



	/**
	 * @inheritDocs
	 * @param array $params Query params array, it could be sequential or associative array. 
	 *						This parameter can be used as an infinite argument for the function.
	 * @return \MvcCore\Ext\Models\Db\Readers\Multiple
	 */
	public function FetchAll ($params = []) {
		if (func_num_args() > 1 || (func_num_args() > 0 && !is_array(func_get_arg(0))))
			$params = func_get_args();
		if (is_array($params))
			$this->params = & $params;
		$this->reader = new \MvcCore\Ext\Models\Db\Readers\Multiple($this);
		return $this->reader;
	}

	/**
	 * @inheritDocs
	 * @param array $params Query params array, it could be sequential or associative array. 
	 *						This parameter can be used as an infinite argument for the function.
	 * @return \MvcCore\Ext\Models\Db\Readers\Stream
	 */
	public function StreamAll ($params = []) {
		if (func_num_args() > 1 || (func_num_args() > 0 && !is_array(func_get_arg(0))))
			$params = func_get_args();
		if (is_array($params))
			$this->params = & $params;
		$this->reader = new \MvcCore\Ext\Models\Db\Readers\Stream($this);
		return $this->reader;
	}

	/**
	 * @inheritDocs
	 * @param array $params Query params array, it could be sequential or associative array. 
	 *						This parameter can be used as an infinite argument for the function.
	 * @return \MvcCore\Ext\Models\Db\Readers\Single
	 */
	public function FetchOne ($params = []) {
		if (func_num_args() > 1 || (func_num_args() > 0 && !is_array(func_get_arg(0))))
			$params = func_get_args();
		if (is_array($params))
			$this->params = & $params;
		$this->reader = new \MvcCore\Ext\Models\Db\Readers\Single($this);
		return $this->reader;
	}

	/**
	 * @inheritDocs
	 * @param array $params Query params array, it could be sequential or associative array. 
	 *						This parameter can be used as an infinite argument for the function.
	 * @return \MvcCore\Ext\Models\Db\Readers\Execution
	 */
	public function Execute ($params = []) {
		if (func_num_args() > 1 || (func_num_args() > 0 && !is_array(func_get_arg(0))))
			$params = func_get_args();
		if (is_array($params))
			$this->params = & $params;
		$this->reader = new \MvcCore\Ext\Models\Db\Readers\Execution($this);
		$this->reader->GetExecResult();
		$this->Close();
		return $this->reader;
	}
}
