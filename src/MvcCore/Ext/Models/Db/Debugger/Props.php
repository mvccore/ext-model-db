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

namespace MvcCore\Ext\Models\Db\Debugger;

/**
 * @mixin \MvcCore\Ext\Models\Db\Debugger
 */
trait Props {

	/**
	 * Singleton debugger instance.
	 * @var \MvcCore\Ext\Models\Db\Debugger|NULL
	 */
	protected static $instance = NULL;

	/**
	 * PHP's `debug_backtrace()` function first argument to get query stack trace.
	 * @var int
	 */
	protected static $stackFlags = DEBUG_BACKTRACE_IGNORE_ARGS/* | DEBUG_BACKTRACE_PROVIDE_OBJECT*/;
	
	/**
	 * PHP's `debug_backtrace()` function second argument to get query stack trace.
	 * @var int
	 */
	protected static $stackLimit = 0; // 0 means unlimited

	/**
	 * Base namespace where current extension is located.
	 * @var \string[]|NULL
	 */
	protected static $baseNamespaces = NULL;

	/**
	 * Store with populated queries.
	 * Each store record is `\stdClass` with keys
	 * `query`, `params`, 'reqTime', 'resTime', `stack` and `connection`.
	 * @var \stdClass[]
	 */
	protected $store = [];

}