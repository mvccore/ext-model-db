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

trait Merging {
	
	use \MvcCore\Model\Connection, 
		\MvcCore\Ext\Models\Db\Model\Connection {
			\MvcCore\Ext\Models\Db\Model\Connection::GetConnection insteadof \MvcCore\Model\Connection;
			\MvcCore\Model\Connection::GetConnection as GetProviderConnection;
		}

	use \MvcCore\Model\MetaData,
		\MvcCore\Ext\Models\Db\Model\MetaData {
			\MvcCore\Ext\Models\Db\Model\MetaData::getMetaData insteadof \MvcCore\Model\MetaData;
			\MvcCore\Ext\Models\Db\Model\MetaData::parseMetaData insteadof \MvcCore\Model\MetaData;
			\MvcCore\Ext\Models\Db\Model\MetaData::parseMetaDataProperty insteadof \MvcCore\Model\MetaData;
			\MvcCore\Model\MetaData::parseMetaDataProperty as parseMetaDataPropertyBase;
	}
	
	use \MvcCore\Model\Parsers,
		\MvcCore\Ext\Models\Db\Model\Parsers {
			\MvcCore\Ext\Models\Db\Model\Parsers::parseToTypes insteadof \MvcCore\Model\Parsers;
			\MvcCore\Ext\Models\Db\Model\Parsers::parseToType insteadof \MvcCore\Model\Parsers;
			\MvcCore\Ext\Models\Db\Model\Parsers::parseToDateTime insteadof \MvcCore\Model\Parsers;
			\MvcCore\Model\Parsers::parseToDateTime as parseToDateTimeDefault;
	}
}