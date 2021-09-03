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
trait Features {
	
	use \MvcCore\Model\Comparers;
	use \MvcCore\Model\Config;
	use \MvcCore\Model\Converters;
	use \MvcCore\Model\Props;

	use \MvcCore\Ext\Models\Db\Model\DataMethods;
	use \MvcCore\Ext\Models\Db\Model\EditMethods;
	use \MvcCore\Ext\Models\Db\Model\Props;
	use \MvcCore\Ext\Models\Db\Model\Provider;
	
	use \MvcCore\Model\MagicMethods,
		\MvcCore\Ext\Models\Db\Model\MagicMethods {
			\MvcCore\Ext\Models\Db\Model\MagicMethods::jsonSerialize insteadof \MvcCore\Model\MagicMethods;
			\MvcCore\Model\MagicMethods::jsonSerialize as jsonSerializeBase;
		}

	use \MvcCore\Model\Connection, 
		\MvcCore\Ext\Models\Db\Model\Connection {
			\MvcCore\Ext\Models\Db\Model\Connection::GetConnection insteadof \MvcCore\Model\Connection;
			\MvcCore\Model\Connection::GetConnection as protected getProviderConnection;
		}

	use \MvcCore\Model\MetaData,
		\MvcCore\Ext\Models\Db\Model\MetaData {
			\MvcCore\Ext\Models\Db\Model\MetaData::GetMetaData insteadof \MvcCore\Model\MetaData;
			\MvcCore\Ext\Models\Db\Model\MetaData::parseMetaData insteadof \MvcCore\Model\MetaData;
			\MvcCore\Ext\Models\Db\Model\MetaData::parseMetaDataProperty insteadof \MvcCore\Model\MetaData;
			\MvcCore\Model\MetaData::parseMetaDataProperty as parseMetaDataPropertyBase;
	}

	use \MvcCore\Model\Resources,
		\MvcCore\Ext\Models\Db\Model\Resources {
			\MvcCore\Ext\Models\Db\Model\Resources::GetCommonResource insteadof \MvcCore\Model\Resources;
			\MvcCore\Ext\Models\Db\Model\Resources::GetResource insteadof \MvcCore\Model\Resources;
			\MvcCore\Model\Resources::GetCommonResource as protected getCommonResourceBase;
			\MvcCore\Model\Resources::GetResource as protected getResourceBase;
	}
	
	use \MvcCore\Model\Parsers,
		\MvcCore\Ext\Models\Db\Model\Parsers {
			\MvcCore\Ext\Models\Db\Model\Parsers::parseToTypes insteadof \MvcCore\Model\Parsers;
			\MvcCore\Ext\Models\Db\Model\Parsers::parseToType insteadof \MvcCore\Model\Parsers;
			\MvcCore\Ext\Models\Db\Model\Parsers::parseToDateTime insteadof \MvcCore\Model\Parsers;
			\MvcCore\Model\Parsers::parseToDateTime as parseToDateTimeDefault;
	}
}