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

namespace TYPO3\CMS\Assist\AI\Platform;

use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Bridge\Generic\EmbeddingsModel;
use Symfony\AI\Platform\Capability;
use TYPO3\CMS\Assist\Domain\Enum\AuthenticationType;

/**
 * Provides additional low-level details for specific platforms.
 */
final readonly class PlatformDetails
{
    private const ADDITIONAL_DETAILS = [
        'mittwald/symfony-ai-platform' => [
            'endpointParam' => 'baseUrl',
            'models' => [
                'endpoint' => '/v1/models',
                'response' => [
                    'listKey' => 'data',
                    'idKey' => 'id',
                ],
            ],
            'authentication' => [
                'type' => AuthenticationType::bearer,
                'param' => 'apiKey',
            ],
        ],
        'symfony/ai-anthropic-platform' => [
            'endpointParam' => 'baseUrl',
            'models' => [
                'endpoint' => '/v1/models',
                'mappings' => [
                    'response' => [
                        'listKey' => 'data',
                        'idKey' => 'id',
                    ],
                ],
            ],
            'endpoints' => [
                'models' => '/v1/models',
            ],
            'authentication' => [
                'type' => AuthenticationType::header,
                'name' => 'x-api-key',
                'param' => 'apiKey',
            ],
            'headers' => [
                'anthropic-version' => '2023-06-01',
            ],
        ],
        'symfony/ai-lm-studio-platform' => [
            'endpointParam' => 'baseUrl',
            'models' => [
                'endpoint' => '/api/v1/models',
                'mappings' => [
                    'response' => [
                        'idKey' => 'key',
                        'listKey' => 'models',
                        'typeKey' => 'type',
                    ],
                    'modelCapability' => [
                        'vision' => Capability::INPUT_IMAGE,
                        'trained_for_tool_use' => Capability::TOOL_CALLING,
                    ],
                    'typeCapability' => [
                        'llm' => [Capability::INPUT_MESSAGES, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING],
                        'embedding' => [Capability::INPUT_TEXT, Capability::EMBEDDINGS],
                    ],
                    'typeModel' => [
                        'llm' => CompletionsModel::class,
                        'embedding' => EmbeddingsModel::class,
                    ],
                ],
            ],
        ],
        'symfony/ai-ollama-platform' => [
            'endpointParam' => 'hostUrl',
            'models' => [
                'endpoint' => '/api/tags',
                'mappings' => [
                    'response' => [
                        'listKey' => 'models',
                        'idKey' => 'name',
                    ],
                ],
            ],
        ],
        'symfony/ai-open-ai-platform' => [
            'endpointParam' => 'baseUrl',
            'models' => [
                'endpoint' => '/v1/models',
                'mappings' => [
                    'response' => [
                        'listKey' => 'data',
                        'idKey' => 'id',
                    ],
                ],
            ],
            'authentication' => [
                'type' => AuthenticationType::bearer,
                'param' => 'apiKey',
            ],
        ],
    ];

    public function getAuthenticationName(string $platform): ?string
    {
        return $this->get($platform)['authentication']['name'] ?? null;
    }

    public function getPlatformEndpointParam(string $platform): ?string
    {
        return $this->get($platform)['endpointParam'] ?? null;
    }

    public function getModelsEndpoint(string $platform): ?string
    {
        return $this->get($platform)['models']['endpoint'] ?? null;
    }

    public function getModelsMappings(string $platform): ?array
    {
        return $this->get($platform)['models']['mappings'] ?? null;
    }

    public function getAuthenticationType(string $platform): ?AuthenticationType
    {
        return $this->get($platform)['authentication']['type'] ?? null;
    }

    public function getAuthenticationParam(string $platform): ?string
    {
        return $this->get($platform)['authentication']['param'] ?? null;
    }

    public function getAdditionalHeaders(string $platform): array
    {
        return $this->get($platform)['headers'] ?? [];
    }

    public function getModelMapping(string $platform): array
    {
        return $this->get($platform)['modelMapping'] ?? [];
    }

    public function getCapabilityMapping(string $platform): array
    {
        return $this->get($platform)['capabilityMapping'] ?? [];
    }

    private function get(string $platform): ?array
    {
        return self::ADDITIONAL_DETAILS[$platform] ?? null;
    }
}
