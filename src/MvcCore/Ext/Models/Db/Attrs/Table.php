<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Ext\Models\Db\Attrs;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_FUNCTION)]
class Table {
	
	const PHP_DOCS_TAG_NAME = '@table';

	
	/**
	 * Define this param to declare table name or table names 
	 * for whole class, where could be used static helper functions
	 * to get table in query by class describtion. Better for refactoring.
	 * @param string|\string[] $names
	 */
	public function __construct ($names) {
	}
}
