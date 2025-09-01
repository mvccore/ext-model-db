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

namespace MvcCore\Ext\Models\Db\Model;

/**
 * @mixin \MvcCore\Ext\Models\Db\Model
 */
trait Parsers {

	/**
	 * Try to convert raw database value into first type in target types.
	 * @param  mixed              $rawValue
	 * @param  array<int, string> $typesString
	 * @param  array              $parserArgs
	 * @return mixed              Converted result.
	 */
	public static function ParseToTypes ($rawValue, $typesString, $parserArgs = []) {
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
					) = static::parseToType($item, $arrayItemTypeString, $parserArgs);
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
				) = static::parseToType($rawValue, $typeString, $parserArgs);
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
	 * @param  mixed  $rawValue
	 * @param  string $typeStr
	 * @param  array  $parserArgs
	 * @return array  First item is conversion boolean success, second item is converted result.
	 */
	protected static function parseToType ($rawValue, $typeStr, $parserArgs = []) {
		$conversionResult = FALSE;
		$typeStr = trim($typeStr, '\\?');
		if (static::parseIsTypeString($typeStr)) {
			// string:
			$rawValue = (string) $rawValue;
			$conversionResult = TRUE;
		} else if (static::parseIsTypeNumeric($typeStr)) {
			// int or float:
			$conversionResult = settype($rawValue, $typeStr);
		} else if (static::parseIsTypeBoolean($typeStr)) {
			// bool:
			$rawValue = static::parseToBool($rawValue);
			$conversionResult = TRUE;
		} else if (static::parseIsTypeDateTime($typeStr)) {
			// \DateTime, \DateTimeImmutable or it's extended class:
			if (!($rawValue instanceof \DateTime || $rawValue instanceof \DateTimeImmutable)) {
				if (is_array($parserArgs) && count($parserArgs) > 0) {
					$dateTime = static::parseToDateTime($typeStr, $rawValue, $parserArgs);
				} else {
					$dateTime = static::parseToDateTimeDefault($typeStr, $rawValue, ['+Y-m-d H:i:s']);
				}
				if ($dateTime !== FALSE) {
					$rawValue = $dateTime;
					$conversionResult = TRUE;
				}
			}
		} else {
			// array or object:
			$rawValue = static::parseToArrayOrObject($typeStr, $rawValue, $parserArgs);
			$conversionResult = TRUE;
		}
		return [$conversionResult, $rawValue];
	}
	
	/**
	 * Convert int, float or string value into \DateTime or it's extended class.
	 * @param  string                       $typeStr
	 * @param  int|float|string|null        $rawValue
	 * @param  array<int|string,mixed>|null $parserArgs 
	 * @return \DateTime|\DateTimeImmutable|bool
	 */
	protected static function parseToDateTime ($typeStr, $rawValue, $parserArgs) {
		/** @var string $dateTimeFormat */
		$dateTimeFormat = $parserArgs[0];
		$dateTimeFormat = '!' . ltrim($dateTimeFormat, '!'); // to reset all other values not included in format into zeros
		if (is_numeric($rawValue)) {
			$rawValueStr = str_replace(['+','-','.'], '', (string) $rawValue);
			$secData = mb_substr($rawValueStr, 0, 10);
			$dateTimeStr = date($dateTimeFormat, intval($secData));
			if (strlen($rawValueStr) > 10)
				$dateTimeStr .= '.' . mb_substr($rawValueStr, 10);
		} else {
			$dateTimeStr = (string) $rawValue;
		}
		if (isset($parserArgs['tz'])) {
			$timeZone = new \DateTimeZone((string) $parserArgs['tz']);
			$dateTime = $typeStr::createFromFormat($dateTimeFormat, $dateTimeStr, $timeZone);
		} else {
			$dateTime = @$typeStr::createFromFormat($dateTimeFormat, $dateTimeStr);
		}
		return $dateTime;
	}

}
