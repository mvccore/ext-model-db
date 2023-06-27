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

interface IStatement {

	/**
	 * Automatically close statement after data are fetched by reader.
	 * @var int
	 */
	const AUTO_CLOSE = 256;
	
	/**
	 * Do not automatically close statement after data are fetched by (first) reader.
	 * @var int
	 */
	const DO_NOT_AUTO_CLOSE = 512;



	/**
	 * Prepares a statement for execution and returns a statement object.
	 * @param  string|\string[]                $sql 
	 * @param  string|int|array|\stdClass|NULL $connectionNameOrConfig
	 * @param  array                           $driverOptions
	 * @throws \PDOException|\Throwable
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public static function Prepare ($sql, $connectionNameOrConfig = NULL, $driverOptions = []);



	/**
	 * Return connection wrapper instance.
	 * @return \MvcCore\Ext\Models\Db\Connection
	 */
	public function GetConnection ();

	/**
	 * Return internal `\PDO` connection instance.
	 * @return \PDO
	 */
	public function GetProvider ();

	/**
	 * Return internal \PDOStatement instance.
	 * @return \PDOStatement
	 */
	public function GetProviderStatement ();
	
	/**
	 * Return `\PDO::prepare()` second argument (`$driver_options`) values.
	 * @return array
	 */
	public function GetDriverOptions ();

	/**
	 * Return execution params.
	 * @return array Query params array, it could be sequential or associative array. 
	 */
	public function GetParams ();

	/**
	 * Set execution params.
	 * @param  array $params Query params array, it could be sequential or associative array. 
	 *                       This parameter can be used as an infinite argument for the function.
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public function SetParams ($params = []);

	/**
	 * Returns prepared statement execution result.
	 * (the `\PDOStatement::execute()` result).
	 * @throws \RuntimeException
	 * @return bool
	 */
	public function GetExecResult ();

	/**
	 * Close the statement cursor, enable the statement to be executed again.
	 * @return bool
	 */
	public function Close ();

	/**
	 * Return `NULL` if statement is prepared and not executed yet.
	 * Return `TRUE` if statement is executed and if cursor is not closed yet.
	 * Return `FALSE` if statement cursor is closed.
	 * @return bool
	 */
	public function IsOpened ();

	/**
	 * Move statement result to the next rowset in a multi-rowset statement handle.
	 * @throws \RuntimeException
	 * @return bool
	 */
	public function NextResultSet ();

	/**
	 * Returns the number of columns in the result set.
	 * @throws \RuntimeException
	 * @return int
	 */
	public function GetColumnsCount ();


	/**
	 * Executes a statement with given params, 
	 * fetches all data from database at once and 
	 * returns multiple rows reader object.
	 * @param  array $params Query params array, it could be sequential or associative array. 
	 *                       This parameter can be used as an infinite argument for the function.
	 * @return \MvcCore\Ext\Models\Db\Readers\Multiple
	 */
	public function FetchAll ($params = []);
	
	/**
	 * Executes a statement with given params, 
	 * doesn't fetch data from database yet, 
	 * only returns multiple rows stream reader object.
	 * @param  array $params Query params array, it could be sequential or associative array. 
	 *                       This parameter can be used as an infinite argument for the function.
	 * @return \MvcCore\Ext\Models\Db\Readers\Stream
	 */
	public function StreamAll ($params = []);
	
	/**
	 * Executes a statement with given params, 
	 * fetches single row from database and 
	 * returns single row reader object.
	 * @param  array $params Query params array, it could be sequential or associative array. 
	 *                       This parameter can be used as an infinite argument for the function.
	 * @return \MvcCore\Ext\Models\Db\Readers\Single
	 */
	public function FetchOne ($params = []);
	
	/**
	 * Executes a statement with given params and 
	 * returns execution reader object.
	 * @param  array $params Query params array, it could be sequential or associative array. 
	 *                       This parameter can be used as an infinite argument for the function.
	 * @throws \PDOException|\Throwable
	 * @return \MvcCore\Ext\Models\Db\Readers\Execution
	 */
	public function Execute ($params = []);
}