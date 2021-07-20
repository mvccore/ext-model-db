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

namespace MvcCore\Ext\Models\Db\Model;

interface IConstants {

	/**
	 * Transaction isolation mode repeatable read.
	 * @var int
	 */
	const TRANS_ISOLATION_REPEATABLE_READ	= 1;
	
	/**
	 * Transaction isolation mode read commited.
	 * @var int
	 */
	const TRANS_ISOLATION_READ_COMMITTED	= 2;
	
	/**
	 * Transaction isolation mode read uncommited.
	 * @var int
	 */
	const TRANS_ISOLATION_READ_UNCOMMITTED	= 4;
	
	/**
	 * Transaction isolation mode serializable.
	 * @var int
	 */
	const TRANS_ISOLATION_SERIALIZABLE		= 8;


	/**
	 * Switch to get metadata properties map keyed by source code properties names.
	 * @var int
	 */
	const METADATA_BY_CODE			= 0;
	
	/**
	 * Switch to get metadata properties map keyed by database column names.
	 * @var int
	 */
	const METADATA_BY_DATABASE		= 1;
	
	/**
	 * Switch to get metadata properties map, where is/are primary key(s) decorated.
	 * @var int
	 */
	const METADATA_PRIMARY_KEY		= 2;
	
	/**
	 * Switch to get metadata properties map, where is/are unique key(s) decorated.
	 * @var int
	 */
	const METADATA_UNIQUE_KEY		= 3;
	
	/**
	 * Switch to get metadata properties map, where is autoincrement feature decorated.
	 * @var int
	 */
	const METADATA_AUTO_INCREMENT	= 4;

	/**
	 * Switch to get metadata class connection attribute ctor arguments.
	 * @var int
	 */
	const METADATA_CONNECTIONS		= 5;

	/**
	 * Switch to get metadata class table attribute ctor arguments.
	 * @var int
	 */
	const METADATA_TABLES			= 6;

	/**
	 * Connection debugger interface.
	 * @var string
	 */
	const DEBUGGER_INTERFACE						= '\\MvcCore\\Ext\\Models\\Db\\IDebugger';
}