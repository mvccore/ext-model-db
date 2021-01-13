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

trait Connection {

	/**
	 * @inheritDocs
	 * @param string|int|array|\stdClass|NULL $connectionNameOrConfig
	 * @param bool $strict	If `TRUE` and no connection under given name or given
	 *						index found, exception is thrown. `TRUE` by default.
	 *						If `FALSE`, there could be returned connection by
	 *						first available configuration.
	 * @throws \InvalidArgumentException
	 * @return \MvcCore\Ext\Models\Db\Connection
	 */
	public static function GetConnection ($connectionNameOrConfig = NULL, $strict = TRUE) {
		/** @var $connection \MvcCore\Ext\Models\Db\Connection */
		$defaultConnectionClassOrig = static::$defaultConnectionClass;
		static::$defaultConnectionClass = static::$providerConnectionClass;
		$error = NULL;
		try {
			$connection = self::GetProviderConnection($connectionNameOrConfig, TRUE);
		} catch (\Throwable $e) {
			$error = $e;
			if ($connectionNameOrConfig === NULL) {
				list(,$callerInfo) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

				if (!isset($callerInfo['class']))
					throw new \RuntimeException(
						"Database static connection getter has to be called from class only."
					);
	
				$getMetaDataMethod = new \ReflectionMethod(ltrim($callerInfo['class']), 'getMetaData');
				$getMetaDataMethod->setAccessible(TRUE);
				list(/*$metaData*/, $connAttrArgs) = $getMetaDataMethod->invokeArgs(
					NULL, [0, [\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_CONNECTIONS]]
				);

				if ($connAttrArgs > 0) 
					$connectionNameOrConfig = $connAttrArgs[0];
				if ($connectionNameOrConfig !== NULL) {
					/** @var $connection \MvcCore\Ext\Models\Db\Connection */
					$connection = \MvcCore\Model::GetConnection($connectionNameOrConfig, TRUE);
					$error = NULL;
				}
			}
		}
		if ($error !== NULL)
			throw $error;
		static::$defaultConnectionClass = $defaultConnectionClassOrig;
		return $connection;
	}
}