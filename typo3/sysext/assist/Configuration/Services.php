<?php

declare(strict_types=1);

namespace TYPO3\CMS\Assist;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Assist\Attribute\AsAssistant;
use TYPO3\CMS\Assist\Domain\Enum\AssistantCapability;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerAttributeForAutoconfiguration(
        AsAssistant::class,
        static function (ChildDefinition $definition, AsAssistant $attribute): void {
            $definition->setPublic(true);
            $definition->addTag(AsAssistant::TAG_NAME, [
                'identifier' => $attribute->identifier,
                'mode' => $attribute->mode->value,
                'capabilities' => json_encode(array_map(
                    static fn(AssistantCapability $c) => $c->value,
                    $attribute->capabilities,
                )),
                'triggerTypes' => json_encode($attribute->triggerTypes),
                'triggerRecords' => json_encode($attribute->triggerRecords),
                'triggerComponents' => json_encode($attribute->triggerComponents),
            ]);
        }
    );
    $containerBuilder->addCompilerPass(new DependencyInjection\AssistantCompilerPass(AsAssistant::TAG_NAME));
};
