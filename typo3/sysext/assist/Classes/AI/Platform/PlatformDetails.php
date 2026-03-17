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

use Symfony\AI\Platform\Bridge\Anthropic\Claude;
use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Bridge\Generic\EmbeddingsModel;
use Symfony\AI\Platform\Bridge\Ollama\Ollama;
use Symfony\AI\Platform\Capability;
use TYPO3\CMS\Assist\Domain\Enum\AuthenticationType;

/**
 * Provides additional low-level details for specific platforms.
 */
final readonly class PlatformDetails
{
    private const ADDITIONAL_DETAILS = [
        /**
        // @todo API does not provide any more details on models
        'mittwald/symfony-ai-platform' => [
            'baseUrl' => 'https://api.mittwald.de/',
            'models' => [
                'listEndpoint' => [
                    'path' => '/v1/models',
                    'http' => [
                        'method' => 'GET',
                    ],
                ],
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
         */
        'symfony/ai-anthropic-platform' => [
            'baseUrl' => 'https://api.anthropic.com/',
            'models' => [
                'listEndpoint' => [
                    'path' => '/v1/models',
                    'http' => [
                        'method' => 'GET',
                    ],
                ],
                'mappings' => [
                    'response' => [
                        'listKey' => 'data',
                        'idKey' => 'id',
                        'typeKey' => 'type',
                    ],
                    'typeCapability' => [
                        'model' => [Capability::INPUT_MESSAGES, Capability::INPUT_IMAGE, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING, Capability::TOOL_CALLING],
                    ],
                    'typeModel' => [
                        'model' => Claude::class,
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
                'listEndpoint' => [
                    'path' => '/api/v1/models',
                    'http' => [
                        'method' => 'GET',
                    ],
                ],
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
                'listEndpoint' => [
                    'path' => '/api/tags',
                    'http' => [
                        'method' => 'GET',
                    ],
                ],
                'detailEndpoint' => [
                    'path' => '/api/show',
                    'http' => [
                        'method' => 'POST',
                        'bodyKey' => 'model',
                    ],
                ],
                'mappings' => [
                    'response' => [
                        'listKey' => 'models',
                        'idKey' => 'name',
                        'capabilitiesKey' => 'capabilities',
                    ],
                    'defaultModel' => Ollama::class,
                    'capabilityMap' => [
                        'completion' => [Capability::INPUT_MESSAGES, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING],
                        'embedding' => [Capability::INPUT_TEXT, Capability::INPUT_MULTIPLE, Capability::EMBEDDINGS],
                        'tools' => [Capability::TOOL_CALLING],
                        'thinking' => [Capability::THINKING],
                        'vision' => [Capability::INPUT_IMAGE],
                    ],
                ],
            ],
        ],
        'symfony/ai-open-ai-platform' => [
            // @todo does not consider potential region setting
            'baseUrl' => 'https://api.openai.com/',
            'models' => [
                'listEndpoint' => [
                    'path' => '/v1/models',
                    'http' => [
                        'method' => 'GET',
                    ],
                ],
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

    public function getModelsListEndpoint(string $platform): ?array
    {
        return $this->get($platform)['models']['listEndpoint'] ?? null;
    }

    public function getModelsDetailEndpoint(string $platform): ?array
    {
        return $this->get($platform)['models']['detailEndpoint'] ?? null;
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

    public function getBaseUrl(string $platform): ?string
    {
        return $this->get($platform)['baseUrl'] ?? null;
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
