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
trait Methods {

	/**
	 * @inheritDocs
	 * @return \MvcCore\Ext\Models\Db\Debugger
	 */
	public static function GetInstance () {
		/** @var \MvcCore\Ext\Models\Db\Debugger $this */
		if (self::$instance === NULL) {
			$instance = new static();
			$baseNamespace = get_class($instance);
			$lastPos = mb_strrpos($baseNamespace, '\\');
			$baseNamespace = mb_substr($baseNamespace, 0, $lastPos);
			$instance::$baseNamespaces = [$baseNamespace];
			self::$instance = $instance;
		}
		return self::$instance;
	}

	/**
	 * @inheritDocs
	 * @param  string                            $query 
	 * @param  array                             $params 
	 * @param  float                             $reqTime
	 * @param  float                             $resTime
	 * @param  \MvcCore\Ext\Models\Db\Connection $connection
	 * @return \MvcCore\Ext\Models\Db\Debugger
	 */
	public function AddQuery ($query, $params, $reqTime, $resTime, \MvcCore\Ext\Models\Db\IConnection $connection) {
		/** @var \MvcCore\Ext\Models\Db\Debugger $this */
		$stack = debug_backtrace(static::$stackFlags, static::$stackLimit);
		// remove files from this extension:
		$index = 0;
		foreach ($stack as $key => $item) {
			if (isset($item['class'])) {
				$className = $item['class'];
				$baseNamespaceMatched = FALSE;
				foreach (static::$baseNamespaces as $baseNamespace) {
					if (mb_strpos($className, $baseNamespace) === 0) {
						$baseNamespaceMatched = TRUE;
						break;
					}
				}
				if ($baseNamespaceMatched) {
					$index = $key;
				} else {
					break;
				}
			}
		}
		$this->store[] = (object) [
			'query'		=> $query,
			'params'	=> $params,
			'reqTime'	=> $reqTime,
			'resTime'	=> $resTime,
			'stack'		=> array_slice($stack, $index),
			'connection'=> $connection,
		];
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return array
	 */
	public function & GetStore () {
		/** @var \MvcCore\Ext\Models\Db\Debugger $this */
		return $this->store;
	}
	
	/**
	 * @inheritDocs
	 * @param  \stdClass[] $store
	 * @return \MvcCore\Ext\Models\Db\Debugger
	 */
	public function SetStore (array & $store) {
		/** @var \MvcCore\Ext\Models\Db\Debugger $this */
		$this->store = & $store;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Ext\Models\Db\Debugger
	 */
	public function Dispose () {
		/** @var \MvcCore\Ext\Models\Db\Debugger $this */
		$this->store = [];
		return $this;
	}

}