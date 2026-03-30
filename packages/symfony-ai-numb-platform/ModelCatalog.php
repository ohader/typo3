<?php

namespace TYPO3\Symfony\AI\NumbPlatform\Bridge;

use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\ModelCatalog\AbstractModelCatalog;

final class ModelCatalog extends AbstractModelCatalog
{
    /**
     * @param array<string, array{class: class-string, capabilities: list<Capability>}> $additionalModels
     */
    public function __construct(array $additionalModels = [])
    {
        $this->models = array_merge([
            'numb-encore' => [
                'class' => ChatModel::class,
                'capabilities' => [
                    Capability::INPUT_MESSAGES,
                    Capability::INPUT_TEXT,
                    Capability::OUTPUT_TEXT,
                    Capability::TOOL_CALLING,
                ],
            ],
        ], $additionalModels);
    }
}
