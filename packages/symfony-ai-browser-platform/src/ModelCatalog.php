<?php

namespace TYPO3\Symfony\AI\BrowserPlatform\Bridge;

use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\ModelCatalog\AbstractModelCatalog;

/**
 * Lists known WebLLM model IDs and their capabilities.
 *
 * Models are downloaded to the browser's IndexedDB on first use.
 * Sizes are approximate (quantized weights).
 */
final class ModelCatalog extends AbstractModelCatalog
{
    /**
     * @param array<string, array{class: class-string, capabilities: list<Capability>}> $additionalModels
     */
    public function __construct(array $additionalModels = [])
    {
        $this->models = array_merge([
            // ~400 MB — Qwen3 0.6B, text only
            'Qwen3-0.6B-q4f16_1-MLC' => [
                'class' => BrowserModel::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::OUTPUT_TEXT,
                ],
            ],
            // ~3.4 GB — Qwen3 1.7B full precision (q0f16), text only
            'Qwen3-1.7B-q4f16_1-MLC' => [
                'class' => BrowserModel::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::OUTPUT_TEXT,
                ],
            ],
            // ~700 MB — Llama 3.2 1B, text only
            'Llama-3.2-1B-Instruct-q4f32_1-MLC' => [
                'class' => BrowserModel::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::OUTPUT_TEXT,
                ],
            ],
            // ~1.5 GB — Gemma 2 2B, text only
            'gemma-2-2b-it-q4f16_1-MLC' => [
                'class' => BrowserModel::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::OUTPUT_TEXT,
                ],
            ],
            // ~1 GB — Qwen 2.5 1.5B, text only
            'Qwen2.5-1.5B-Instruct-q4f16_1-MLC' => [
                'class' => BrowserModel::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::OUTPUT_TEXT,
                ],
            ],
            // ~5 GB — Hermes-2-Pro Llama 3 8B (q4f16), supports tool calling
            'Hermes-2-Pro-Llama-3-8B-q4f16_1-MLC' => [
                'class' => BrowserModel::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::OUTPUT_TEXT,
                    Capability::TOOL_CALLING,
                ],
            ],
            // ~5 GB — Hermes-2-Pro Mistral 7B (q4f16), supports tool calling
            'Hermes-2-Pro-Mistral-7B-q4f16_1-MLC' => [
                'class' => BrowserModel::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::OUTPUT_TEXT,
                    Capability::TOOL_CALLING,
                ],
            ],
            // ~5 GB — Hermes-3 Llama 3.1 8B (q4f16), supports tool calling
            'Hermes-3-Llama-3.1-8B-q4f16_1-MLC' => [
                'class' => BrowserModel::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::OUTPUT_TEXT,
                    Capability::TOOL_CALLING,
                ],
            ],
        ], $additionalModels);
    }
}
