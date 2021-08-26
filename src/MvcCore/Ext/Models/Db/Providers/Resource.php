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

namespace MvcCore\Ext\Models\Db\Providers;

class		Resource
implements	\MvcCore\Model\IConstants,
			\MvcCore\Ext\Models\Db\Model\IConstants,
			\MvcCore\Ext\Models\Db\Providers\Resources\IEdit {
	
	use \MvcCore\Model\Props;
	use \MvcCore\Model\Config;

	use \MvcCore\Model\Connection, 
		\MvcCore\Ext\Models\Db\Model\Connection {
			\MvcCore\Ext\Models\Db\Model\Connection::GetConnection insteadof \MvcCore\Model\Connection;
			\MvcCore\Model\Connection::GetConnection as GetProviderConnection;
		}
	
	use \MvcCore\Ext\Models\Db\Model\ProviderResource;
	
	use \MvcCore\Ext\Models\Db\Providers\Resources\Edit;
}