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
trait Provider {
	
	/**
	 * Provider specific driver name.
	 * @var ?string
	 */
	protected static $providerDriverName = NULL;

	/**
	 * Connection class full name, specific for each extension.
	 * @var string
	 */
	protected static $providerConnectionClass = '\\MvcCore\\Ext\\Models\\Db\\Connection';

	/**
	 * Edit resource class full name, specific for each extension.
	 * @var string
	 */
	protected static $providerEditResourceClass = '\\MvcCore\\Ext\\Models\\Db\\Resources\\Edit';
}