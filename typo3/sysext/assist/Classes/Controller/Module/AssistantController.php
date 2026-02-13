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

namespace TYPO3\CMS\Assist\Controller\Module;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Assist\Service\AssistantRegistry;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Context\PageContext;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * @internal This class is a specific TYPO3 Backend controller implementation and is not part of the Public TYPO3 API.
 */
#[AsController]
final readonly class AssistantController
{
    public function __construct(
        private AssistantRegistry $assistantRegistry,
        private ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $pageContext = $request->getAttribute('pageContext');
        if (!$pageContext instanceof PageContext) {
            throw new \RuntimeException('Required PageContext not available', 1749900010);
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->assign('assistants', $this->assistantRegistry->getAssistants());

        $site = $pageContext->site;
        if ($site instanceof Site) {
            $siteIdentifier = $site->getIdentifier();
            $configuration = $site->getConfiguration();
            $assistAssistants = $configuration['assistAssistants'] ?? [];

            $assistantModels = [];
            foreach ($assistAssistants as $identifier => $settings) {
                $assistantModels[$identifier] = $settings['model'] ?? '';
            }

            $view->assignMultiple([
                'siteIdentifier' => $siteIdentifier,
                'assistantModels' => json_encode($assistantModels, JSON_THROW_ON_ERROR),
            ]);
        } else {
            $view->assignMultiple([
                'siteIdentifier' => false,
                'assistantModels' => json_encode([], JSON_THROW_ON_ERROR),
            ]);
        }

        return $view->renderResponse('Assistant/Overview');
    }
}
