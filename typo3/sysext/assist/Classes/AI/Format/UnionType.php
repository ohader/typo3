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

namespace TYPO3\CMS\Assist\AI\Format;

/**
 * Schema combinator for a discriminated union of OutputFormatInterface types.
 *
 * @internal
 */
final class UnionType
{
    /**
     * @param list<class-string<OutputFormatInterface>> $types
     */
    private function __construct(private readonly array $types) {}

    /**
     * @param class-string<OutputFormatInterface> ...$types
     */
    public static function of(string ...$types): self
    {
        return new self(array_values($types));
    }

    /** Returns {"oneOf": [...]} */
    public function toJsonSchema(): array
    {
        return ['oneOf' => array_map(
            static fn(string $type) => $type::toJsonSchema(),
            $this->types,
        )];
    }

    /**
     * Dispatches $json to the matching type via ::getType().
     *
     * @throws \UnexpectedValueException For unknown type discriminator (code 1740999601)
     */
    public function parse(array $json): OutputFormatInterface
    {
        $discriminator = $json['type'] ?? '';
        foreach ($this->types as $type) {
            if ($type::getType() === $discriminator) {
                return $type::fromJson($json);
            }
        }
        throw new \UnexpectedValueException(
            sprintf('Unknown output format type "%s".', $discriminator),
            1740999601,
        );
    }
}
