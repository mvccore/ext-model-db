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

interface IConnection {

	/**
	 * Connection debugger interface.
	 * @var string
	 */
	const DEBUGGER_INTERFACE		= '\\MvcCore\\Ext\\Models\\Db\\IDebugger';
	
	/**
	 * SQL metadata statement with fields `AffectedRows` and `LastInsertId`.
	 */
	const METADATA_STATEMENT		= NULL;

	
	/**
	 * Return an array of available `\PDO` drivers.
	 * @return array
	 */
	public static function GetAvailableDrivers ();

	/**
	 * Connect into database by given dsn, credentials and options or thrown an error.
	 * @param  string      $dsn
	 * @param  ?string $username
	 * @param  ?string $password
	 * @param  array       $options
	 * @throws \PDOException|\Throwable
	 */
	public function __construct ($dsn, $username = NULL, $password = NULL, array $options = []);
	
	/**
	 * Get used system configuration values.
	 * @return \stdClass
	 */
	public function GetConfig ();

	/**
	 * Prepares a statement for execution and returns a statement object.
	 * @param  string|\string[] $sql 
	 * @param  int|string       $connectionIndexOrName 
	 * @throws \PDOException|\Throwable
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public function Prepare ($sql, $connectionIndexOrName = NULL);

	/**
	 * Executes an SQL statement and returns a statement object.
	 * @param  string|\string[] $sql
	 * @throws \PDOException|\Throwable
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public function Query ($sql, $connectionIndexOrName = NULL);
	
	/**
	 * Execute an SQL statement and returns a reader object.
	 * @param  string|\string[] $sql
	 * @throws \PDOException|\Throwable
	 * @return \MvcCore\Ext\Models\Db\Readers\Execution
	 */
	public function Execute ($sql, $connectionIndexOrName = NULL);



	/**
	 * Quotes a string for use in a query.
	 * @param  string $string
	 * @param  int    $paramType
	 * @return string
	 */
	public function Quote ($string , $paramType = \PDO::PARAM_STR);
	
	/**
	 * Quote database identifier by provider specfic way, 
	 * usually table or column name.
	 * @param  string $identifierName
	 * @return string
	 */
	public function QuoteName ($identifierName);


	
	/**
	 * Retrieve a `\PDO` database connection attribute.
	 * @param  int $attribute
	 * @return mixed
	 */
	public function GetAttribute ($attribute);

	/**
	 * Set a `\PDO` database connection attribute.
	 * @param  int   $attribute
	 * @param  mixed $value
	 * @return bool
	 */
	public function SetAttribute ($attribute , $value);
	
	/**
	 * Return `TRUE` for transcode from/to database encoding 
	 * to/from client encoding by PHP `iconv()`, `FALSE` by default.
	 * @return bool
	 */
	public function GetTranscode ();
	
	/**
	 * Return `\stdClass` with keys `database` and `client`,
	 * where are charsets for PHP `iconv()` trancoding.
	 * @return \stdClass
	 */
	public function GetTranscodingCharsets ();

	/**
	 * Transcode string value in row data 
	 * from database to client encoding by `iconv()`.
	 * @param  string $str 
	 * @return string
	 */
	public function TranscodeResultValue ($str);
	
	/**
	 * Transcode any string array value in row data 
	 * from database to client encoding by `iconv()`.
	 * @param  array $rowData 
	 * @return array
	 */
	public function TranscodeResultRowValues ($rowData);
	
	/**
	 * Return database server version in "PHP-standardized" version number string.
	 * @return ?string
	 */
	public function GetVersion ();

	/**
	 * Return `TRUE` for multi statements connection type.
	 * @return ?bool
	 */
	public function IsMutliStatements ();

	/**
	 * Return internal `\PDO` database connection instance.
	 * @return \PDO
	 */
	public function GetProvider ();
	
	/**
	 * Get `__construct()` function arguments values.
	 * @return array
	 */
	public function GetCtorArguments ();


	
	/**
	 * Checks if this connection is already inside transaction or not.
	 * @return bool
	 */
	public function InTransaction ();

	/**
	 * Initiates a transaction.
	 * @param  int    $flags Transaction isolation, read/write mode and more, depends on database driver.
	 * @param  string $name  String without spaces to identify transaction in logs.
	 * @throws \PDOException|\RuntimeException
	 * @return bool
	 */
	public function BeginTransaction ($flags = 0, $name = NULL);

	/**
	 * Commits a transaction.
	 * @param  int $flags
	 * @throws \PDOException
	 * @return bool
	 */
	public function Commit ($flags = 0);

	/**
	 * Rolls back a transaction.
	 * @param  int $flags
	 * @throws \PDOException
	 * @return bool
	 */
	public function Rollback ($flags = 0);

	/**
	 * Returns connection debugger instance.
	 * @return ?\MvcCore\Ext\Models\Db\IDebugger
	 */
	public function GetDebugger ();
	
	/**
	 * Sets connection debugger instance.
	 * @param  ?\MvcCore\Ext\Models\Db\IDebugger $debugger
	 * @param  bool                              $copyPreviousQueries
	 *                                           Copy queries from previous debugger if there were any.
	 * @throws \Exception Debugger doesn't implement \MvcCore\Ext\Models\Db\IDebugger interface.
	 * @return \MvcCore\Ext\Models\Db\IConnection
	 */
	public function SetDebugger ($debugger = NULL, $copyPreviousQueries = TRUE);

	/**
	 * Get `TRUE` if connection uses ODBC connection driver, `FALSE` otherwise for most cases.
	 * @return bool
	 */
	public function GetUsingOdbcDriver ();

	/**
	 * Get custom statement to get affected rows count 
	 * by INSERT, UPDATE or DELETE statement and 
	 * to get last inserted id after INSERT statement.
	 * @return ?string
	 */
	public function GetMetaDataStatement ();

	/**
	 * Replace all params in query to dump query with values on development env.
	 * Return array with success boolean and replaced query.
	 * @param  \PDO   $provider
	 * @param  string $query 
	 * @param  array  $params 
	 * @return array  [bool $success, string $replacedQuery]
	 */
	public static function DumpQueryWithParams ($provider, $query, $params);
}