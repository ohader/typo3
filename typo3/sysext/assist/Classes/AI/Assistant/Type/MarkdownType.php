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

namespace TYPO3\CMS\Assist\AI\Assistant\Type;

/**
 * @internal
 */
final readonly class MarkdownType implements TypeInterface
{
    public function __construct(public string $value) {}

    public static function getType(): string
    {
        return 'markdown';
    }

    public static function toJsonSchema(): JsonSchema
    {
        return new JsonSchema([
            'type' => 'object',
            '$comment' => 'Use this type to provide rich text content.',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'const' => 'markdown',
                ],
                'value' => [
                    'type' => 'string',
                    '$comment' => 'Only these Markdown types are allowed: ' .
                        'bold, italic, links, list items, headlines, inline code, code blocks, tables. ' .
                        'Any other types are not allowed.',
                    'examples' => [
                        '**bold text**',
                        '*italic text*',
                        '[Link label](https://example.com)',
                        "- First item\n- Second item\n- Third item",
                        "# Headline\n## Subheadline",
                        '`inline code`',
                        "```php\n\$variable = 'value';\necho \$variable;\n```",
                        "| Column 1 | Column 2 |\n|----------|----------|\n| Cell A   | Cell B   |",
                    ],
                ],
            ],
            'required' => ['type', 'value'],
            'additionalProperties' => false,
            'examples' => [
                ['type' => 'markdown', 'value' => '**bold** and *italic* text'],
                ['type' => 'markdown', 'value' => "| Column 1 | Column 2 |\n|----------|----------|\n| Cell A   | Cell B   |\n"],
            ],
        ]);
    }

    public static function fromJson(array $json): static
    {
        return new static($json['value']);
    }
}
