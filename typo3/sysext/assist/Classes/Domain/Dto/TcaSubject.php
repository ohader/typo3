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

namespace TYPO3\CMS\Assist\Domain\Dto;

/**
 * @internal
 */
final readonly class TcaSubject implements SubjectInterface
{
    /**
     * @param string $tableName Name of the table
     * @param int $uid UID/Identifier of the record
     * @param string $propertyName Property/Field name in the record
     * @param list<string>|null $flexFormPath (optional) FlexForm path to the field
     * @param list<string>|null $types (optional) Type values of the record
     */
    public function __construct(
        public string $tableName,
        public int $uid,
        public string $propertyName,
        public ?array $flexFormPath = null,
        public ?array $types = null,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'tableName' => $this->tableName,
            'uid' => $this->uid,
            'propertyName' => $this->propertyName,
            'flexFormPath' => $this->flexFormPath,
            'types' => $this->types,
        ];
    }

    public static function fromString(string $value): static
    {
        $data = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        return new static(
            tableName: (string)$data['tableName'],
            uid: (int)$data['uid'],
            propertyName: (string)$data['propertyName'],
            flexFormPath: isset($data['flexFormPath']) ? array_map('strval', $data['flexFormPath']) : null,
            types: isset($data['types']) ? array_map('strval', $data['types']) : null,
        );
    }

    public function __toString(): string
    {
        return json_encode(['kind' => 'tca'] + $this->jsonSerialize(), JSON_THROW_ON_ERROR);
    }
}
