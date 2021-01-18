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
	 
namespace MvcCore\Ext\Models\Db\Attrs;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class KeyUnique {
	
	const PHP_DOCS_TAG_NAME = '@keyUnique';

	
	/**
	 * Define this param if column is used in any unique key.
	 * Optionally you can set database key name.
	 * @param string $keyName 
	 */
	public function __construct ($keyName = NULL) {
	}
}