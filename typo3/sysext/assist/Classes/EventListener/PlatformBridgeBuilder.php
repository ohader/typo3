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

namespace TYPO3\CMS\Assist\EventListener;

use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Bridge\Generic\EmbeddingsModel;
use Symfony\AI\Platform\Capability;
use TYPO3\CMS\Assist\Event\BeforeBuildPlatformBridgeEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Uri;

/**
 * Resolves live models by calling LM Studio API directly.
 * The ModelCatalog of Symfony for that platform is hard-coded.
 */
#[AsEventListener('typo3/cms-assist/build-platform-bridge')]
final class PlatformBridgeBuilder
{
    public function __construct(private RequestFactory $requestFactory) {}

    public function __invoke(BeforeBuildPlatformBridgeEvent $event): void
    {
        if ($event->platform->package === 'symfony/ai-lm-studio-platform') {
            $uri = (new Uri($event->platform->options['baseUrl'] ?? ''))
                ->withPath('/api/v1/models');

            $options = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    ...$event->platform->authorization?->getHeaderItem() ?? [],
                ],
            ];
            $response = $this->requestFactory->request(
                (string)$uri,
                'GET',
                $options
            );

            $data = json_decode($response->getBody()->getContents(), true);
            $additionalModels = [];

            foreach ($data['models'] ?? [] as $model) {
                $key = $model['key'] ?? null;
                $type = $model['type'] ?? null;
                if ($key === null || $type === null) {
                    continue;
                }

                $capabilities = match ($type) {
                    'llm' => [Capability::INPUT_MESSAGES, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING],
                    'embedding' => [Capability::INPUT_TEXT, Capability::EMBEDDINGS],
                    default => null,
                };

                if ($capabilities === null) {
                    continue;
                }

                $class = match ($type) {
                    'llm' => CompletionsModel::class,
                    'embedding' => EmbeddingsModel::class,
                };

                if (!empty($model['capabilities']['vision'])) {
                    $capabilities[] = Capability::INPUT_IMAGE;
                }
                if (!empty($model['capabilities']['trained_for_tool_use'])) {
                    $capabilities[] = Capability::TOOL_CALLING;
                }

                $additionalModels[$key] = [
                    'class' => $class,
                    'capabilities' => $capabilities,
                ];
            }

            $event->setOptions([
                ...$event->getOptions(),
                'modelCatalog' => [
                    'additionalModels' => $additionalModels,
                ],
            ]);
        }
    }
}
