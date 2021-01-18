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

namespace MvcCore\Ext\Models\Db\Readers\Streams;

interface IIterator {

	/**
	 * Internal constant to complete result items as custom instances.
	 * @var string
	 */
	const COMPLETER_INSTANCES	= 'instances';
	
	/**
	 * Internal constant to complete result items as arrays.
	 * @var string
	 */
	const COMPLETER_ARRAYS		= 'arrays';
	
	/**
	 * Internal constant to complete result items as `\stdClass` objects.
	 * @var string
	 */
	const COMPLETER_OBJECTS		= 'objects';
	
	/**
	 * Internal constant to complete result items as scalar variables.
	 * @var string
	 */
	const COMPLETER_SCALARS		= 'scalars';
	
	/**
	 * Internal constant to complete result items as any type variables.
	 * @var string
	 */
	const COMPLETER_ANY			= 'any';


	/**
	 * Return reader object wrapper.
	 * @return \MvcCore\Ext\Models\Db\Readers\Stream
	 */
	public function GetReader ();
	
	/**
	 * Return sql statement object wrapper.
	 * @return \MvcCore\Ext\Models\Db\Statement
	 */
	public function GetStatement ();

	/**
	 * Return database connection object wrapper.
	 * @return \MvcCore\Ext\Models\Db\Connection
	 */
	public function GetConnection ();

	/**
	 * Close database cursor - call if you want to break iterator loop earlier.
	 * @return void
	 */
	public function Close ();
}
