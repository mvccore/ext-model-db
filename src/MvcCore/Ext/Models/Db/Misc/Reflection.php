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

namespace MvcCore\Ext\Models\Db\Misc;

class Reflection {

	/**
	 * Prefered PHP classes and properties anontation.
	 * PHP8+ attributes anotation is default.
	 * @var bool
	 */
	protected static $attributesAnotation = TRUE;

	/**
	 * Shortcut to `\MvcCore\Application::GetInstance()->GetToolClass()`.
	 * @var string|NULL
	 */
	private static $_toolClass = NULL;


	/**
	 * Set prefered PHP classes and properties anontation preference.
	 * PHP8+ attributes anotation is default. Set value to `FALSE`
	 * to prefer PhpDocs tags anotation instead.
	 * @param bool $attributesAnotation 
	 * @return bool
	 */
	public static function SetAttributesAnotations ($attributesAnotation = TRUE) {
		return self::$attributesAnotation = $attributesAnotation;
	}
	
	/**
	 * Get prefered PHP classes and properties anontation preference.
	 * PHP8+ attributes anotation is default. `FALSE` value means
	 * to prefer PhpDocs tags anotation instead.
	 * @return bool
	 */
	public static function GetAttributesAnotations () {
		return self::$attributesAnotation;
	}

	/**
	 * Get reflection property attribute ctor arguments (or PhpDocs tags arguments).
	 * @param \ReflectionProperty $prop 
	 * @return \stdClass
	 */
	public static function GetClassPropertyAttrsArgs (\ReflectionProperty $prop) {
		$toolClass = self::$_toolClass ?: (
			self::$_toolClass = \MvcCore\Application::GetInstance()->GetToolClass()
		);
		$result = new \stdClass;
		if (self::$attributesAnotation) {
			$attrsClassesNames = [
				'columnName'	=> 'MvcCore\Ext\Models\Db\Attributes\Column',
				'columnFormat'	=> 'MvcCore\Ext\Models\Db\Attributes\Format',
				'keyPrimary'	=> 'MvcCore\Ext\Models\Db\Attributes\KeyPrimary',
				'keyUnique'		=> 'MvcCore\Ext\Models\Db\Attributes\KeyUnique',
			];
			foreach ($attrsClassesNames as $key => $attrClassName) 
				$result->{$key} = $toolClass::GetAttrCtorArgs(
					$prop, $attrClassName
				);
		} else {
			$phpDocsTagsNames = [
				'columnName'	=> '@column',
				'columnFormat'	=> '@format',
				'keyPrimary'	=> '@keyPrimary',
				'keyUnique'		=> '@keyUnique',
			];
			foreach ($phpDocsTagsNames as $key => $phpDocsTagName) 
				$result->{$key} = $toolClass::GetPhpDocsTagArgs(
					$prop, $phpDocsTagName
				);
		}
		return $result;
	}
	
	/**
	 * Get reflection class attribute ctor arguments (or PhpDocs tags arguments).
	 * @param \ReflectionClass $cls 
	 * @return \stdClass
	 */
	public static function GetClassAttrsArgs (\ReflectionClass $cls) {
		$toolClass = self::$_toolClass ?: (
			self::$_toolClass = \MvcCore\Application::GetInstance()->GetToolClass()
		);
		$result = new \stdClass;
		if (self::$attributesAnotation) {
			$attrsClassesNames = [
				'connections'	=> 'MvcCore\Ext\Models\Db\Attributes\Connection',
				'tables'		=> 'MvcCore\Ext\Models\Db\Attributes\Table',
			];
			foreach ($attrsClassesNames as $key => $attrClassName) 
				$result->{$key} = $toolClass::GetAttrCtorArgs(
					$cls, $attrClassName
				);
		} else {
			$phpDocsTagsNames = [
				'connections'	=> '@connection',
				'tables'		=> '@table',
			];
			foreach ($phpDocsTagsNames as $key => $phpDocsTagName) 
				$result->{$key} = $toolClass::GetPhpDocsTagArgs(
					$cls, $phpDocsTagName
				);
		}
		return $result;
	}
}