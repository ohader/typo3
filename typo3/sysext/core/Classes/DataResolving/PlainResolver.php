<?php
namespace TYPO3\CMS\Core\DataResolving;

use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class PlainResolver {

	/**
	 * @var string
	 */
	protected $tableName;

	/**
	 * @var array
	 */
	protected $liveIds;

	/**
	 * @var string
	 */
	protected $sortingStatement;

	/**
	 * @var int
	 */
	protected $workspaceId;

	/**
	 * @var int
	 */
	protected $languageId;

	/**
	 * @var bool
	 */
	protected $keepLiveIds = FALSE;

	/**
	 * @var bool
	 */
	protected $keepDeletePlaceholder = FALSE;

	/**
	 * @var array
	 */
	protected $resolvedIds;

	public function __construct($tableName, array $liveIds, $sortingStatement = NULL) {
		$this->tableName = $tableName;
		$this->liveIds = $this->reindex($liveIds);
		$this->sortingStatement = $sortingStatement;
	}

	public function setWorkspaceId($workspaceId) {
		$this->workspaceId = (int)$workspaceId;
		return $this;
	}

	public function setLanguageId($languageId) {
		$this->languageId = (int)$languageId;
		return $this;
	}

	public function setKeepLiveIds($keepLiveIds) {
		$this->keepLiveIds = (bool)$keepLiveIds;
		return $this;
	}

	public function setKeepDeletePlaceholder($keepDeletePlaceholder) {
		$this->keepDeletePlaceholder = (bool)$keepDeletePlaceholder;
		return $this;
	}

	/**
	 * @return array
	 */
	public function get() {
		if (isset($this->resolvedIds)) {
			return $this->resolvedIds;
		}

		$ids = $this->processVersionOverlays($this->liveIds);
		$ids = $this->processLocalizationOverlays($ids, $this->languageId);
		$ids = $this->processSorting($ids);
		$ids = $this->applyLiveIds($ids);

		$this->resolvedIds = $ids;
		return $this->resolvedIds;
	}

	protected function processVersionOverlays(array $ids) {
		if (empty($this->workspaceId) || !$this->isWorkspaceEnabled()) {
			return $ids;
		}

		$ids = $this->processVersionMovePlaceholders($ids);
		$versions = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'uid,t3ver_oid,t3ver_state', $this->tableName,
			'pid=-1 AND t3ver_oid IN (' . implode(',', $ids) . ') AND t3ver_wsid=' . $this->workspaceId
		);

		if (!empty($versions)) {
			foreach ($versions as $version) {
				$liveReferenceId = $version['t3ver_oid'];
				$versionId = $version['uid'];
				if (isset($ids[$liveReferenceId])) {
					if (!$this->keepDeletePlaceholder && VersionState::cast($version['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
						unset($ids[$liveReferenceId]);
					} else {
						$ids[$liveReferenceId] = $versionId;
					}
				}
			}
			$ids = $this->reindex($ids);
		}

		return $ids;
	}

	/**
	 * @param array $ids
	 * @return array
	 */
	protected function processVersionMovePlaceholders(array $ids) {
		if (empty($this->workspaceId) || !$this->isVersionMovePlaceholderAware()) {
			return $ids;
		}

		$movePlaceholders = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'uid,t3ver_move_id', $this->tableName,
			'pid<>-1 AND t3ver_state=' . VersionState::MOVE_PLACEHOLDER . ' AND t3ver_wsid=' . $this->workspaceId . ' AND t3ver_move_id IN (' . implode(',', $ids) . ')'
		);

		if (!empty($movePlaceholders)) {
			foreach ($movePlaceholders as $movePlaceholder) {
				$liveReferenceId = $movePlaceholder['t3ver_move_id'];
				$movePlaceholderId = $movePlaceholder['uid'];
				// If both, MOVE_PLACEHOLDER and MOVE_POINTER are set
				if (isset($ids[$liveReferenceId]) && $ids[$movePlaceholderId]) {
					$ids[$movePlaceholderId] = $liveReferenceId;
					unset($ids[$liveReferenceId]);
				}
			}
			$ids = $this->reindex($ids);
		}

		return $ids;
	}

	protected function processLocalizationOverlays(array $ids, $languageId = NULL) {
		if (empty($languageId) || !$this->isLocalizationEnabled()) {
			return $ids;
		}

		$ids = $this->processVersionOverlays($ids);
		return $ids;
	}

	/**
	 * @param array $ids
	 * @return array
	 */
	protected function processSorting(array $ids) {
		if (empty($this->sortingStatement)) {
			return $ids;
		}

		$records = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'uid', $this->tableName,
			'uid IN (' . implode(',', $ids) . ')',
			'', $this->sortingStatement,
			'', 'uid'
		);

		if (!is_array($records)) {
			return array();
		}

		$ids = $this->reindex(array_keys($records));
		return $ids;
	}

	/**
	 * @param array $ids
	 * @return array
	 */
	protected function applyLiveIds(array $ids) {
		if (!$this->keepLiveIds || !$this->isWorkspaceEnabled()) {
			return $ids;
		}

		$records = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'uid,t3ver_oid', $this->tableName,
			'uid IN (' . implode(',', $ids) . ')',
			'', '',
			'', 'uid'
		);

		if (!is_array($records)) {
			return array();
		}

		foreach ($ids as $id) {
			if (!empty($records[$id]['t3ver_oid'])) {
				$ids[$id] = $records[$id]['t3ver_oid'];
			}
		}

		$ids = $this->reindex($ids);
		return $ids;
	}

	/**
	 * @param array $ids
	 * @return array
	 */
	protected function reindex(array $ids) {
		if (empty($ids)) {
			return $ids;
		}
		$ids = array_values($ids);
		$ids = array_combine($ids, $ids);
		return $ids;
	}

	/**
	 * Move to TCA Utility
	 * @return bool
	 */
	protected function isWorkspaceEnabled() {
		return BackendUtility::isTableWorkspaceEnabled($this->tableName);
	}

	/**
	 * Move to TCA Utility
	 * @return bool
	 */
	protected function isVersionMovePlaceholderAware() {
		return BackendUtility::isTableMovePlaceholderAware($this->tableName);
	}

	/**
	 * Move to TCA Utility
	 * @return bool
	 */
	protected function isLocalizationEnabled() {
		return BackendUtility::isTableLocalizable($this->tableName);
	}

	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
