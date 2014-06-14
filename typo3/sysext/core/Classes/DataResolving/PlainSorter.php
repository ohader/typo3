<?php
namespace TYPO3\CMS\Core\DataResolving;

use TYPO3\CMS\Core\Versioning\VersionState;

class PlainSorter implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @param string $tableName
	 * @param array $ids
	 * @param string $sortingStatement
	 * @return array
	 */
	public function get($tableName, array $ids, $sortingStatement) {
		if (empty($sortingStatement)) {
			return $ids;
		}

		$records = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'uid', $tableName,
			'uid IN (' . implode(',', $ids) . ')',
			'', $sortingStatement,
			'', 'uid'
		);

		if (!is_array($records)) {
			return array();
		}

		return array_keys($records);
	}

	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
