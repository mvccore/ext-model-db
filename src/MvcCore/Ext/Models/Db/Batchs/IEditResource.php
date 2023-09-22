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

namespace MvcCore\Ext\Models\Db\Batchs;

interface IEditResource extends \MvcCore\Ext\Models\Db\Resources\IEdit {
	
	/**
	 * Reset params counter to number all batch params at the betch beginning.
	 * @return \MvcCore\Ext\Models\Db\Batchs\EditResource
	 */
	public function ResetParamsCounter ();

	/**
	 * Set batch edit handler to handle completed operation type, SQL and params.
	 * `\Closure` function returning void and accepting arguments:
	 *  - int $sqlOperation - type of SQL operation by enum `IBatch::OPERATION_*`,
	 *  - string $sqlCode   - raw sql code to execute operation,
	 *  - array $params     - database operation params.
	 * @param  callable|\Closure $editHandler 
	 * @return \MvcCore\Ext\Models\Db\Batchs\EditResource
	 */
	public function SetEditHandler ($editHandler);

}