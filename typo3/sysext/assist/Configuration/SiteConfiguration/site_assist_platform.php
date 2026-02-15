<?php

use Symfony\Component\Config\Resource\ComposerResource;
use TYPO3\CMS\Assist\Backend\PlatformTcaProvider;
use TYPO3\CMS\Assist\Service\PackageService;

return (new PlatformTcaProvider(new PackageService(new ComposerResource())))->getTca();
