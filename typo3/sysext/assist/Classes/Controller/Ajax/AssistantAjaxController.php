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

namespace TYPO3\CMS\Assist\Controller\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Assist\Domain\Enum\AssistantMode;
use TYPO3\CMS\Assist\Domain\Model\Assistant;
use TYPO3\CMS\Assist\Service\AssistantRegistry;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * @internal This class is a specific TYPO3 Backend controller implementation and is not part of the Public TYPO3 API.
 */
#[AsController]
final readonly class AssistantAjaxController
{
    public function __construct(
        private AssistantRegistry $assistantRegistry,
    ) {}

    public function getInlineAssistants(ServerRequestInterface $request): ResponseInterface
    {
        $record = $request->getQueryParams()['record'] ?? '';
        $table = $record !== '' ? explode(':', $record, 2)[0] : '';
        $assistants = $this->assistantRegistry->getAssistantsByMode(AssistantMode::inline);
        if ($table !== '') {
            $assistants = array_filter(
                $assistants,
                static fn(Assistant $a): bool =>
                    $a->trigger->resources === [] || $a->trigger->hasResource($table),
            );
        }
        return new JsonResponse(array_values(array_map(
            static fn(Assistant $assistant): array => [
                'identifier' => $assistant->identifier,
                'label' => $assistant->label,
            ],
            $assistants,
        )));
    }
}
