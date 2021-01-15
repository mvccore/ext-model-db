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

namespace MvcCore\Ext\Models\Db\Attrs;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Column {

	const PHP_DOCS_TAG_NAME = '@column';

	/**
	 * Define database column name.
	 * @param string $name
	 */
	public function __construct ($name) {
	}
}