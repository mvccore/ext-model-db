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

namespace MvcCore\Ext\Models\Db\Readers;

class		Execution 
extends		\MvcCore\Ext\Models\Db\Reader
implements	\MvcCore\Ext\Models\Db\Readers\IExecution {
	
	/**
	 * @inheritDocs
	 * @param  string|NULL $sequenceName
	 * @param  string|NULL $targetType
	 * @throws \PDOException|\Throwable
	 * @return int|float|string|NULL
	 */
	public function LastInsertId ($sequenceName = NULL, $targetType = NULL) {
		$conn = $this->statement->GetConnection();
		if ($metaStatement = $conn->GetMetaDataStatement()) {
			$metaData = $this->getMetaData($conn, $metaStatement);
			$result = $metaData['LastInsertId'];
		} else {
			$result = $this->provider->lastInsertId($sequenceName);
		}
		if ($result !== NULL && $targetType !== NULL)
			settype($result, $targetType);
		return $result;
	}
	
	/**
	 * @inheritDocs
	 * @throws \PDOException|\Throwable
	 * @return int
	 */
	public function GetRowsCount () {
		$conn = $this->statement->GetConnection();
		if ($metaStatement = $conn->GetMetaDataStatement()) {
			$metaData = $this->getMetaData($conn, $metaStatement);
			return intval($metaData['AffectedRows']);
		} else {
			return $this->statement->GetProviderStatement()->rowCount();
		}
	}
	
	/**
	 * @inheritDocs
	 * @throws \PDOException|\Throwable
	 * @return int
	 */
	public function GetAllResultsRowsCount () {
		$rowsCount = $this->GetRowsCount();
		while ($this->statement->NextResultSet()) 
			$rowsCount += $this->GetRowsCount();
		$this->statement->Close();
		return $rowsCount;
	}
}