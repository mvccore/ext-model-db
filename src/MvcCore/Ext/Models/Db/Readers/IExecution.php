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

namespace MvcCore\Ext\Models\Db\Readers;

interface IExecution extends \MvcCore\Ext\Models\Db\IReader {

	/**
	 * Returns the number of rows affected by the last SQL statement.
	 * (the `\PDOStatement::rowCount()` result).
	 * @return int
	 */
	public function GetRowsCount ();
}