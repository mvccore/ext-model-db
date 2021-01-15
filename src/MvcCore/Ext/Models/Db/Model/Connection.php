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
		if ($connectionNameOrConfig === NULL) {
			
			list(,$callerInfo) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

			if (!isset($callerInfo['class']))
				throw new \RuntimeException(
					"[".get_called_class()."] Database static connection getter has to be called from class only."
				);
			try {
				$getMetaDataMethod = new \ReflectionMethod(ltrim($callerInfo['class']), 'getMetaData');
				$getMetaDataMethod->setAccessible(TRUE);
				list(/*$metaData*/, $connAttrArgs) = $getMetaDataMethod->invokeArgs(
					NULL, [0, [\MvcCore\Ext\Models\Db\Model\IConstants::METADATA_CONNECTIONS]]
				);

				if ($connAttrArgs > 0) 
					$connectionNameOrConfig = $connAttrArgs[0];
			} catch (\Throwable $e) {
				
			}
		}

		$connectionName = (is_string($connectionNameOrConfig) || is_int($connectionNameOrConfig))
			? $connectionNameOrConfig
			: static::resolveConnectionName($connectionNameOrConfig, $strict);
		
		if (isset(self::$connections[$connectionName])) 
			return self::$connections[$connectionName];
		
		$cfg = static::GetConfig($connectionName);
		if ($cfg === NULL) throw new \InvalidArgumentException(
			"[".get_called_class()."] No connection found under given name/index: `{$connectionName}`."
		);
		
		// if there is no connection class in config 
		// and config driver is the same as current provider class name,
		// set connection class into this provider class name:
		$sysCfgProps = static::$sysConfigProperties;
		$sysConfigClassProp = $sysCfgProps['class'];
		if (!isset($cfg->{$sysConfigClassProp})) {
			$providerDriverName = static::$providerDriverName;
			if ($providerDriverName !== NULL) {
				if ($providerDriverName === $cfg->driver) {
					$cfg = (object) array_merge([], (array) $cfg); // clone the `\stdClass` before change
					$cfg->{$sysConfigClassProp} = static::$providerConnectionClass;
				} else {
					throw new \RuntimeException(
						"[".get_called_class()."] Default connection has different driver ".
						"name `{$cfg->driver}` than current class is extended from."
					);
				}
			}
		}
		
		// connect:
		$connection = static::connect($cfg);
		
		// store new connection under config index for all other model classes:
		self::$connections[$connectionName] = $connection;
		
		return $connection;
	}
}