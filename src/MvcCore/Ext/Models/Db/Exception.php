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

namespace MvcCore\Ext\Models\Db;

class Exception extends \Exception {

	/** @var string|NULL */
	protected $query = NULL;

	/** @var array|NULL */
	protected $params = NULL;

	/**
	 * @param \Throwable $e 
	 * @return \MvcCore\Ext\Models\Db\Exception
	 */
	public static function Create (\Throwable $e) {
		return new static (
			$e->getMessage(),
			intval($e->getCode()),
			$e
		);
	}

	/**
	 * @param string $message 
	 * @param int|NULL $code 
	 * @param \Throwable|NULL $previous 
	 * @param string|NULL $query 
	 * @param array|NULL $params 
	 */
	public function __construct ($message = "", $code = 0, $previous = NULL, $query = NULL, $params = NULL) {
		parent::__construct($message, $code, $previous);
		$this->query = $query;
		$this->params = $params;
	}

	/**
	 * @return string|NULL
	 */
	public function getQuery () {
		return $this->query;
	}

	/**
	 * @return array|NULL
	 */
	public function getParams () {
		return $this->params;
	}

	/**
	 * @param string|NULL $query
	 * @return \MvcCore\Ext\Models\Db\Exception
	 */
	public function setQuery ($query) {
		$this->query = $query;
		return $this;
	}

	/**
	 * @param array|null $params
	 * @return \MvcCore\Ext\Models\Db\Exception
	 */
	public function setParams ($params) {
		$this->params = $params;
		return $this;
	}
}