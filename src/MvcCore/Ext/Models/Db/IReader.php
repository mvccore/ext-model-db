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

interface IReader {

	/**
	 * Returns prepared statement execution result.
	 * (the `\PDOStatement::execute()` result).
	 * @throws \PDOException|\Throwable
	 * @return bool
	 */
	public function GetExecResult ();

	/**
	 * Set prepared statement execution result from previous reader.
	 * (the `\PDOStatement::execute()` result).
	 * @param  bool|NULL $execResult
	 * @return \MvcCore\Ext\Models\Db\Reader
	 */
	public function SetExecResult ($execResult);

	/**
	 * Return raw fetched data. This function returns `NULL` for stream reader.
	 * @return array|NULL
	 */
	public function GetRawData ();

	/**
	 * Return reader statement object.
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public function GetStatement ();
}
