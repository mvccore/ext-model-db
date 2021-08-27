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

/**
 * @mixin \MvcCore\Ext\Models\Db\Model
 */
trait Props {

	/**
	 * Model metadata cache class to cache parsed metadata.
	 * Class has to implement `\MvcCore\Ext\ICache` or class
	 * has to implement static method `GetStore()` and instance 
	 * methods `Load()` and `Save()`.
	 * @var string
	 */
	protected static $metaDataCacheClass	= '\\MvcCore\\Ext\\Cache';

	/**
	 * Model metadata base cache key.
	 * @var string
	 */
	protected static $metaDataCacheKeyBase	= 'model.meta';

	/**
	 * Model metadata cache tags.
	 * @var \string[]
	 */
	protected static $metaDataCacheTags		= ['modelmeta'];

	/**
	 * Default properties flags for data and manipulation methods:
	 *	- data methods: `GetValues()`, `SetValues()`, `GetTouched()`
	 *	- maniulation methods: `Save()`, `IsNew()`, `Insert()`, `Update()`, `Delete()`
	 * @var int
	 */
	protected static $defaultPropsFlags		= \MvcCore\IModel::PROPS_INHERIT_PROTECTED;
	
	/**
	 * Database provider specific resource class instance with universal SQL statements.
	 * @var \MvcCore\Ext\Models\Db\Resources\Edit|NULL
	 */
	protected $editResource = NULL;
}