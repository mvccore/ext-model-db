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

namespace MvcCore\Ext\Models\Db\Batchs;

/**
 * Responsibility - batch model flush operation data store.
 */
class FlushData extends \stdClass {
	
	/**
	 * SQL operations flags - keys are sql operation 
	 * indexes, values are sql operation flags.
	 * @var \int[]
	 */
	public $SqlOperations		= [];

	/**
	 * Batch model object instances indexes, 
	 * keyed by SQL operations indexes.
	 * @var \int[]
	 */
	public $InstanceIndexes		= [];

	/**
	 * SQL operations, keyed by SQL operations indexes.
	 * @var \string[]
	 */
	public $SqlCodes			= [];

	/**
	 * All SQL operations params, keys are param 
	 * names, values are param values.
	 * @var array
	 */
	public $Params				= [];

	/**
	 * Current instance index used in 
	 * SQL operations completing.
	 * @var int
	 */
	public $InstanceIndex		= 0;

	/**
	 * `TRUE` to use metadata SQL queries to get 
	 * last inserted id or affected rows count.
	 * Usually used for ODBC drivers.
	 * @var bool
	 */
	public $UseMetaStatement	= FALSE;

	/**
	 * SQL query to get last inserted id 
	 * or affected rows count.
	 * @var string
	 */
	public $MetaStatement		= NULL;

	/**
	 * Database metadata statement for affected rows 
	 * and last inserted id if provider doesn|t support it.
	 * @param  string|NULL $metaStatement 
	 * @return void
	 */
	public function __construct ($metaStatement) {
		$this->UseMetaStatement	= $metaStatement !== NULL;
		$this->MetaStatement	= $metaStatement;
	}
}