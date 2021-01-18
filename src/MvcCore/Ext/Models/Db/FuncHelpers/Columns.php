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

namespace MvcCore\Ext\Models\Db\FuncHelpers;

/**
 * Return columns names by described class properties, joined by separator,
 * optionally without some columns names as second argument.
 * @param string $separator 
 * @param \string[] $exceptColumns
 * @return string
 */
function Columns ($separator = ',', $exceptColumns = []) {
	list(,$callerInfo) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
	
	if (!isset($callerInfo['class']))
		throw new \RuntimeException(
			"Columns helper function has to be called from class only."
		);
	
	$getMetaDataMethod = new \ReflectionMethod(ltrim($callerInfo['class']), 'getMetaData');
	$getMetaDataMethod->setAccessible(TRUE);
	list(/*$metaData*/, $dbColumnNamesMap) = $getMetaDataMethod->invokeArgs(
		NULL, [0, [\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_BY_DATABASE]]
	);

	$columns = array_keys($dbColumnNamesMap);
	if (count($exceptColumns) > 0) 
		$columns = array_diff($columns, $exceptColumns);

	return implode($separator, $columns);
}
