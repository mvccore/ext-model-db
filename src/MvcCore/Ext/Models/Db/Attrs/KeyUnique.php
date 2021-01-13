<?php<?php

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

namespace MvcCore\Ext\Models\Dbs\Attrs;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class KeyUnique {
	/**
	 * Define this param if column is used in any unique key.
	 * Optionally you can set database key name.
	 * @param string $keyName 
	 */
	public function __construct ($keyName = NULL) {
	}
}