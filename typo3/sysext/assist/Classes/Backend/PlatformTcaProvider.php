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

namespace TYPO3\CMS\Assist\Backend;

use TYPO3\CMS\Assist\AI\Platform\PlatformReflector;
use TYPO3\CMS\Assist\Service\PackageService;

/**
 * Builds the complete TCA array for `site_assist_platform` by reflecting on
 * each installed platform's `PlatformFactory::create()` method.
 *
 * Instantiated manually at TCA load time (no DI available).
 */
final readonly class PlatformTcaProvider
{
    private const LABEL_PREFIX = 'assist.siteconfiguration_tca:site_assist_platform.';

    /**
     * Parameter names that have dedicated XLF labels.
     */
    private const KNOWN_LABELS = [
        'apiKey',
        'baseUrl',
        'hostUrl',
        'region',
        'supportsCompletions',
        'supportsEmbeddings',
        'completionsPath',
        'embeddingsPath',
    ];

    public function __construct(
        private PackageService $packageService,
    ) {}

    public function getTca(): array
    {
        $columns = $this->getBaseColumns();
        $types = [
            '1' => [
                'showitem' => 'enabled, name, package',
            ],
        ];

        $packageNames = $this->packageService->findPackageNamesByType(PackageService::SYMFONY_AI_PLATFORM);

        foreach ($packageNames as $packageName) {
            $params = $this->reflectFactoryParameters($packageName);
            $paramNames = [];
            $columnsOverrides = [];

            foreach ($params as $param) {
                $columnName = $param['name'];
                if (!isset($columns[$columnName])) {
                    $columns[$columnName] = $this->buildColumnConfig($param);
                }
                $override = $this->buildColumnOverride($param);
                if ($override !== []) {
                    $columnsOverrides[$columnName] = $override;
                }
                $paramNames[] = $columnName;
            }

            $showitem = 'enabled, name, package';
            if ($paramNames !== []) {
                $showitem .= ', --linebreak--, ' . implode(', ', $paramNames);
            }
            $typeConfig = ['showitem' => $showitem];
            if ($columnsOverrides !== []) {
                $typeConfig['columnsOverrides'] = $columnsOverrides;
            }
            $types[$packageName] = $typeConfig;
        }

        return [
            'ctrl' => [
                'label' => 'name',
                'type' => 'package',
                'title' => self::LABEL_PREFIX . 'ctrl.title',
                'typeicon_classes' => [
                    'default' => 'mimetypes-x-content-domain',
                ],
                'sortby' => '@transient',
            ],
            'columns' => $columns,
            'types' => $types,
        ];
    }

    private function getBaseColumns(): array
    {
        return [
            'enabled' => [
                'label' => self::LABEL_PREFIX . 'enabled',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'default' => 0,
                ],
            ],
            'name' => [
                'label' => self::LABEL_PREFIX . 'name',
                'config' => [
                    'type' => 'input',
                    'required' => true,
                    'eval' => 'trim',
                ],
            ],
            'package' => [
                'label' => self::LABEL_PREFIX . 'package',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'required' => true,
                    'itemsProcFunc' => PlatformItemsProcFunc::class . '->getInstalledPlatformPackages',
                    'items' => [
                        ['label' => '', 'value' => ''],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array{name: string, type: string, sensitive: bool, required: bool, default: mixed}>
     */
    private function reflectFactoryParameters(string $packageName): array
    {
        try {
            $namespace = $this->packageService->getPackageNamespace($packageName);
        } catch (\InvalidArgumentException) {
            return [];
        }

        $reflector = new PlatformReflector($namespace);
        $factoryClass = $reflector->getPlatformFactoryClassName();
        if (!class_exists($factoryClass)) {
            return [];
        }

        try {
            $method = new \ReflectionMethod($factoryClass, 'create');
        } catch (\ReflectionException) {
            return [];
        }

        $sensitiveNames = $reflector->getPlatformFactorySensitiveOptionsNames();
        $params = [];
        foreach ($method->getParameters() as $reflectionParam) {
            $type = $reflectionParam->getType();
            if (!$type instanceof \ReflectionNamedType || !$type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();
            if (!in_array($typeName, ['int', 'string', 'bool'], true)) {
                continue;
            }

            $sensitive = in_array($reflectionParam->getName(), $sensitiveNames, true);
            $hasDefault = $reflectionParam->isDefaultValueAvailable();
            $default = $hasDefault ? $reflectionParam->getDefaultValue() : null;

            $params[] = [
                'name' => $reflectionParam->getName(),
                'type' => $typeName,
                'sensitive' => $sensitive,
                'required' => !$hasDefault && !$type->allowsNull(),
                'default' => $default,
            ];
        }

        return $params;
    }

    /**
     * Builds the shared column definition (label + base type/eval).
     * Per-type specifics (required, placeholder) go into columnsOverrides.
     */
    private function buildColumnConfig(array $param): array
    {
        $name = $param['name'];
        $label = in_array($name, self::KNOWN_LABELS, true)
            ? self::LABEL_PREFIX . $name
            : $this->humanizeParameterName($name);

        if ($param['type'] === 'bool') {
            return [
                'label' => $label,
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'default' => $param['default'] ? 1 : 0,
                ],
            ];
        }

        $eval = 'trim';
        if ($param['sensitive']) {
            $eval = 'trim';
            // @todo type password not implemented in site-config (which would be wrong as well)
            // $eval = 'trim,password';
        }

        return [
            'label' => $label,
            'config' => [
                'type' => 'input',
                'eval' => $eval,
            ],
        ];
    }

    /**
     * Builds per-type overrides for required and placeholder.
     */
    private function buildColumnOverride(array $param): array
    {
        if ($param['type'] === 'bool') {
            return [];
        }

        $config = [];
        if ($param['required']) {
            $config['required'] = true;
        }
        if ($param['default'] !== null && $param['default'] !== '') {
            $config['default'] = (string)$param['default'];
            $config['placeholder'] = (string)$param['default'];
        }

        if ($config === []) {
            return [];
        }

        return ['config' => $config];
    }

    private function humanizeParameterName(string $camelCase): string
    {
        $words = preg_replace('/([a-z])([A-Z])/', '$1 $2', $camelCase);
        return ucfirst($words);
    }
}
