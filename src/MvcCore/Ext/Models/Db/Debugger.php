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

class Debugger implements \MvcCore\Ext\Models\Db\IDebugger {

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
	 * @var string|NULL
	 */
	protected static $baseNamespace = NULL;

	/**
	 * Store with populated queries.
	 * Each store record is `\stdClass` with keys
	 * `query`, `params`, 'exec', `stack` and `connection`.
	 * @var \stdClass[]
	 */
	protected $store = [];

	/**
	 * @inheritDocs
	 * @return \MvcCore\Ext\Models\Db\Debugger
	 */
	public static function GetInstance () {
		if (self::$instance === NULL) {
			$instance = new static();
			$baseNamespace = get_class($instance);
			$lastPos = mb_strrpos($baseNamespace, '\\');
			$baseNamespace = mb_substr($baseNamespace, 0, $lastPos);
			$instance::$baseNamespace = $baseNamespace;
			self::$instance = $instance;
		}
		return self::$instance;
	}

	/**
	 * @inheritDocs
	 * @param  string                            $query 
	 * @param  array                             $params 
	 * @param  float                             $execMs
	 * @param  \MvcCore\Ext\Models\Db\Connection $connection
	 * @return \MvcCore\Ext\Models\Db\Debugger
	 */
	public function AddQuery ($query, $params, $execMs, \MvcCore\Ext\Models\Db\IConnection $connection) {
		$stack = debug_backtrace(static::$stackFlags, static::$stackLimit);
		// remove files from this extension:
		$index = 0;
		foreach ($stack as $key => $item) {
			if (isset($item['class'])) {
				$className = $item['class'];
				if (mb_strpos($className, static::$baseNamespace) === 0) {
					$index = $key;
				} else {
					break;
				}
			}
		}
		$this->store[] = (object) [
			'query'		=> $query,
			'params'	=> $params,
			'exec'		=> $execMs,
			'stack'		=> array_slice($stack, $index + 1),
			'connection'=> $connection,
		];
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return array
	 */
	public function & GetStore () {
		return $this->store;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Ext\Models\Db\Debugger
	 */
	public function Dispose () {
		$this->store = [];
		return $this;
	}

}