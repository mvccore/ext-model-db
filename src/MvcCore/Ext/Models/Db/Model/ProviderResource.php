<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Ext\Models\Db\Model;

trait ProviderResource {
	
	/**
	 * Connection class full name, specific for each extension.
	 * @var string
	 */
	protected static $providerConnectionClass = '\\MvcCore\\Ext\\Models\\Db\\Connection';

	/**
	 * Database provider specific resource class instance with universal SQL statements.
	 * @var \MvcCore\Ext\Models\Db\Providers\Resources\Base
	 */
	protected static $editProviderResource = NULL;

	/**
	 * Get database provider specific resource class instance with universal SQL statements.
	 * @return \MvcCore\Ext\Models\Db\Providers\Resources\Base
	 */
	protected static function getEditProviderResource () {
		if (self::$editProviderResource === NULL)
			self::$editProviderResource = new \MvcCore\Ext\Models\Db\Providers\Resources\Base;
		return self::$editProviderResource;
	}
}