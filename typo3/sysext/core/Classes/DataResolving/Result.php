<?php
namespace TYPO3\CMS\Core\DataResolving;

class Context {

	protected $languageIds;

	protected $workspaceId;

	protected $useVersionIds = FALSE;

	public function getLanguageIds() {
		return $this->languageIds;
	}

	public function getWorkspaceId() {
		return $this->workspaceId;
	}

}
