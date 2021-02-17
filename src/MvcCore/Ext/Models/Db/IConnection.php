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
	 * Return an array of available `\PDO` drivers.
	 * @return array
	 */
	public static function GetAvailableDrivers ();

	/**
	 * Connect into database by given dsn, credentials and options or thrown an error.
	 * @param  string      $dsn
	 * @param  string|NULL $username
	 * @param  string|NULL $password
	 * @param  array       $options
	 * @throws \Throwable
	 */
	public function __construct ($dsn, $username = NULL, $password = NULL, array $options = []);

	/**
	 * Prepares a statement for execution and returns a statement object.
	 * @param  string|\string[] $sql 
	 * @param  int|string       $connectionIndexOrName 
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public function Prepare ($sql, $connectionIndexOrName = NULL);

	/**
	 * Executes an SQL statement and returns a statement object.
	 * @param  string|\string[] $sql
	 * @throws \Throwable
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public function Query ($sql, $connectionIndexOrName = NULL);
	
	/**
	 * Execute an SQL statement and returns a reader object.
	 * @param  string|\string[] $sql
	 * @throws \Throwable
	 * @return \MvcCore\Ext\Models\Db\Readers\Execution
	 */
	public function Execute ($sql, $connectionIndexOrName = NULL);



	/**
	 * Returns the ID of the last inserted row or sequence value.
	 * @param  string|NULL $sequenceName
	 * @param  string|NULL $targetType
	 * @return int|float|string|NULL
	 */
	public function LastInsertId ($sequenceName = NULL, $targetType = NULL);

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
	 * @param int   $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function SetAttribute ($attribute , $value);
	
	/**
	 * Return database server version in "PHP-standardized" version number string.
	 * @return null|string
	 */
	public function GetVersion ();

	/**
	 * Return `TRUE` for multi statements connection type.
	 * @return bool|null
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
	public function GetConfig ();


	
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
}