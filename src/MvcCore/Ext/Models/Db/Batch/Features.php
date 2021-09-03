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

namespace MvcCore\Ext\Models\Db\Batch;

/**
 * @mixin \MvcCore\Ext\Models\Db\Batch
 */
trait Features {
	
	use \MvcCore\Model\Props;
	use \MvcCore\Model\Config;

	use \MvcCore\Ext\Models\Db\Batch\Props;
	use \MvcCore\Ext\Models\Db\Batch\GettersSetters;
	use \MvcCore\Ext\Models\Db\Batch\Flushing;

	use \MvcCore\Model\Connection, 
		\MvcCore\Ext\Models\Db\Model\Connection {
			\MvcCore\Ext\Models\Db\Model\Connection::GetConnection insteadof \MvcCore\Model\Connection;
			\MvcCore\Model\Connection::GetConnection as protected getProviderConnection;
		}
}