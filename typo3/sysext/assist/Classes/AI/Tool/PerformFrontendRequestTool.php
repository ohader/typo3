<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Assist\AI\Tool;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Assist\Service\BackendUserPermissionService;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Frontend\Http\Application;

#[Autoconfigure(public: true)]
#[AsTool(
    name: 'typo3-assist-performFrontendRequest',
    description: 'Fetches the rendered HTML of a TYPO3 page by page UID. ' .
        'Returns the visible text content (tags stripped) including title, headings, and body text.',
)]
final readonly class PerformFrontendRequestTool
{
    public function __construct(
        private Application $application,
        private SiteFinder $siteFinder,
        private BackendUserPermissionService $permissionService,
        private Context $context,
    ) {}

    /**
     * @param int $pageUid The UID of the page to render
     * @param int $languageId The language UID to use (default: 0)
     */
    public function __invoke(int $pageUid, int $languageId = 0): string
    {
        if (!$this->permissionService->isInWebMount($pageUid)) {
            return '';
        }

        $site = $this->siteFinder->getSiteByPageId($pageUid);

        try {
            $language = $site->getLanguageById($languageId);
        } catch (\InvalidArgumentException) {
            $language = $site->getDefaultLanguage();
        }

        $uri = $site->getRouter()->generateUri($pageUid, ['_language' => $language]);

        $subRequest = (new ServerRequest($uri, 'GET'))
            ->withAttribute('site', $site);

        $savedBeUser = $GLOBALS['BE_USER'] ?? null;
        $savedTypo3Request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        // @todo this should be encapsulated better
        $savedBackendUserAspect = $this->context->getAspect('backend.user');
        $savedWorkspaceAspect = $this->context->getAspect('workspace');

        try {
            $response = $this->application->handle($subRequest);
        } finally {
            $GLOBALS['BE_USER'] = $savedBeUser;
            $GLOBALS['TYPO3_REQUEST'] = $savedTypo3Request;
            $this->context->setAspect('backend.user', $savedBackendUserAspect);
            $this->context->setAspect('workspace', $savedWorkspaceAspect);
        }

        if ($response->getStatusCode() >= 300) {
            return '';
        }

        return strip_tags((string)$response->getBody());
    }
}
