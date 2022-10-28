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

/**
 * @inheritDocs
 */
class		Model 
implements	\MvcCore\IModel,
			\MvcCore\Ext\Models\Db\IModel, 
			\MvcCore\Ext\Models\Db\Model\IConstants,
			\JsonSerializable {

	use \MvcCore\Ext\Models\Db\Model\Features;
	
	/**
	 * MvcCore Extension - Model - Db - version:
	 * Comparison by PHP function version_compare();
	 * @see http://php.net/manual/en/function.version-compare.php
	 */
	const VERSION = '5.1.15';
}