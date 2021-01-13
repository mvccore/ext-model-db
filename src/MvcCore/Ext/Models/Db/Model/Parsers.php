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

trait Parsers {

	/**
	 * Try to convert raw database value into first type in target types.
	 * @param mixed $rawValue
	 * @param \string[] $typesString
	 * @param array $formatArgs
	 * @return mixed Converted result.
	 */
	protected static function parseToTypes ($rawValue, $typesString, $formatArgs = []) {
		$targetTypeValue = NULL;
		$value = $rawValue;
		foreach ($typesString as $typeString) {
			if (substr($typeString, -2, 2) === '[]') {
				if (!is_array($value)) {
					$value = trim(strval($rawValue));
					$value = $value === '' ? [] : explode(',', $value);
				}
				$arrayItemTypeString = substr($typeString, 0, strlen($typeString) - 2);
				$targetTypeValue = [];
				$conversionResult = TRUE;
				foreach ($value as $key => $item) {
					list(
						$conversionResultLocal, $targetTypeValueLocal
					) = static::parseToType($item, $arrayItemTypeString, $formatArgs);
					if ($conversionResultLocal) {
						$targetTypeValue[$key] = $targetTypeValueLocal;
					} else {
						$conversionResult = FALSE;
						break;
					}
				}
			} else {
				list(
					$conversionResult, $targetTypeValue
				) = static::parseToType($rawValue, $typeString, $formatArgs);
			}
			if ($conversionResult) {
				$value = $targetTypeValue;
				break;
			}
		}
		return $value;
	}

	/**
	 * Try to convert database value into target type.
	 * @param mixed $rawValue
	 * @param string $typeStr
	 * @param array $formatArgs
	 * @return array First item is conversion boolean success, second item is converted result.
	 */
	protected static function parseToType ($rawValue, $typeStr, $formatArgs = []) {
		$conversionResult = FALSE;
		$typeStr = trim($typeStr, '\\');
		if ($typeStr == 'DateTime') {
			if (!($rawValue instanceof \DateTime)) {
				if (count($formatArgs) > 0) {
					$dateTime = static::parseToDateTime($rawValue, $formatArgs);
				} else {
					$dateTime = static::parseToDateTimeDefault($rawValue, 'Y-m-d H:i:s.u');
				}
				if ($dateTime instanceof \DateTime) {
					$rawValue = $dateTime;
					$conversionResult = TRUE;
				}
			}
		} else {
			// bool, int, float, string, array, object, null:
			if (settype($rawValue, $typeStr)) 
				$conversionResult = TRUE;
		}
		return [$conversionResult, $rawValue];
	}
	
	/**
	 * Convert int, float or string value into \DateTime.
	 * @param int|float|string|NULL $rawValue 
	 * @param \string[] $formatArgs 
	 * @return \DateTime|bool
	 */
	protected static function parseToDateTime ($rawValue, $formatArgs) {
		$dateTimeFormat = $formatArgs[0];
		if (is_numeric($rawValue)) {
			$rawValueStr = str_replace(['+','-','.'], '', (string) $rawValue);
			$secData = mb_substr($rawValueStr, 0, 10);
			$dateTimeStr = date($dateTimeFormat, intval($secData));
			if (strlen($rawValueStr) > 10)
				$dateTimeStr .= '.' . mb_substr($rawValueStr, 10);
		} else {
			$dateTimeStr = (string) $rawValue;
		}
		$dateTime = \date_create_from_format($dateTimeFormat, $dateTimeStr);
		if ($dateTime !== FALSE && isset($formatArgs[1])) {
			try {
				$dateTime->setTimezone(new \DateTimeZone((string) $formatArgs[1]));
			} catch (\Exception $e) {	
			}
		}
		return $dateTime;
	}

}
