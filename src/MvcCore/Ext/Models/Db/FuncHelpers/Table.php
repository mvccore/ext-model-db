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

namespace MvcCore\Ext\Models\Db\FuncHelpers;

/**
 * Return described table name by index.
 * @param int $tableIndex 
 * @throws \RuntimeException|\InvalidArgumentException
 * @return string
 */
function Table ($tableIndex = 0) {
	list(,$callerInfo) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
	
	if (!isset($callerInfo['class']))
		throw new \RuntimeException(
			"Table helper function has to be called from class only."
		);
	
	$getMetaDataMethod = new \ReflectionMethod(ltrim($callerInfo['class']), 'getMetaData');
	$getMetaDataMethod->setAccessible(TRUE);
	list(/*$metaData*/, $tableAttrArgs) = $getMetaDataMethod->invokeArgs(
		NULL, [0, [\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_TABLES]]
	);
	
	if (!isset($tableAttrArgs[$tableIndex])) 
		throw new \InvalidArgumentException(
			"Table decoration under index `{$tableIndex}` doesn't exist."
		);

	return $tableAttrArgs[$tableIndex];
}
