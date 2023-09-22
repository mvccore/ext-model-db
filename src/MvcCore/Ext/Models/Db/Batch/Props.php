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

namespace MvcCore\Ext\Models\Db\Batch;

/**
 * @mixin \MvcCore\Ext\Models\Db\Batch
 */
trait Props {
	
	/**
	 * Automaticaly flush batch after flush size is exceeded.
	 * @var bool
	 */
	protected $autoFlush = TRUE;
	
	/**
	 * Default flush size is `10` items.
	 * @var int
	 */
	protected $flushSize = 10;
	
	/**
	 * Current instances array size.
	 * @var int
	 */
	protected $size = 0;

	/**
	 * Instances store.
	 * @var \MvcCore\Ext\Models\Db\Model[]
	 */
	protected $instances = [];

	/**
	 * Operations flags to execute on collected instances.
	 * @var \int[]
	 */
	protected $operationsFlags = [];

	/**
	 * Connection name or index.
	 * @var string|int|NULL
	 */
	protected $connectionName = NULL;

	/**
	 * Connection instance.
	 * @var \MvcCore\Ext\Models\Db\Connection|NULL
	 */
	protected $connection = NULL;

	/**
	 * Flush execution data store.
	 * @var \MvcCore\Ext\Models\Db\Batchs\FlushData|NULL
	 */
	protected $flushData = NULL;

	/**
	 * Affected rows for all queries in last flush call.
	 * @var int
	 */
	protected $rowsCount = 0;

	/**
	 * Inserted instances metadata cache.
	 * @var array
	 */
	protected $instancesMetaData = [];
	
	/**
	 * The editing resource type of the model instance in the batch. 
	 * An instance of this type is inserted into each instance of the model 
	 * in the batch before the command is executed to the database.
	 * @var \ReflectionClass|NULL
	 */
	protected $instanceEditResourceType = NULL;
	
	/**
	 * Constructor arguments for new instance of editing resource.
	 * @var array
	 */
	protected $instanceEditResourceCtorArgs = [];

}