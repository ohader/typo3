<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * TYPO3 DB specific query settings like use of a PID
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: BackendInterface.php 2120 2009-04-02 10:06:31Z k-fish $
 */
class Tx_Extbase_Persistence_Storage_Typo3DbSpecificQuerySettings implements Tx_Extbase_Persistence_Storage_BackendSpecificQuerySettingsInterface {

	protected $useStoragePage = TRUE;

	protected $useEnableFields = TRUE;
	
	protected $storagePageId;
	
	public function useStoragePage($useStoragePage) {
		$this->useStoragePage = (boolean)$useStoragePage;
	}
	
	public function storagePageEnabled() {
		return $this->useStoragePage;
	}

	public function getStoragePageId() {
		return $this->storagePageId;
	}
	public function setStoragePageId($storagePageId) {
		$this->storagePageId = $storagePageId;
	}
	public function enableFieldsEnabled() {
		return $this->useEnableFields;
	}
	public function useEnableFields($useEnableFields) {
		$this->useEnableFields = $useEnableFields;
	}
}
?>