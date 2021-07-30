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

interface IDebugger {

	/**
	 * Return or create singleton debugger instance.
	 * @return \MvcCore\Ext\Models\Db\IDebugger
	 */
	public static function GetInstance ();

	/**
	 * Add query with params into local store.
	 * @param  string                             $query 
	 * @param  array                              $params 
	 * @param  float                              $reqTime
	 * @param  float                              $resTime
	 * @param  \MvcCore\Ext\Models\Db\IConnection $connection
	 * @return \MvcCore\Ext\Models\Db\IDebugger
	 */
	public function AddQuery ($query, $params, $reqTime, $resTime, \MvcCore\Ext\Models\Db\IConnection $connection);
	
	/**
	 * Get populated store.
	 * @return array
	 */
	public function & GetStore ();

	/**
	 * Set store of populated queries.
	 * @param  \stdClass[] $store
	 * @return \MvcCore\Ext\Models\Db\Debugger
	 */
	public function SetStore (array & $store);

	/**
	 * Frees local store memory.
	 * @return \MvcCore\Ext\Models\Db\IDebugger
	 */
	public function Dispose ();

}