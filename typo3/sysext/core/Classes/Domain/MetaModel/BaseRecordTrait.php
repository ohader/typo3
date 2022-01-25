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

namespace TYPO3\CMS\Core\Domain\MetaModel;

trait BaseRecordTrait
{
    protected array $fields;
    protected RecordType $recordType;
    public function getIdentifier()
    {
        return $this->fields['uid'];
    }
    public function getPid(): int
    {
        return $this->fields['pid'];
    }
    public function getType(): RecordType
    {
        return $this->recordType;
    }
    public function has(string $fieldName): bool
    {
        return isset($this->fields[$fieldName]);
    }

    public function get(string $fieldName)
    {
        if (!$this->has($fieldName)) {
            throw new \InvalidArgumentException($fieldName);
        }
        return $this->fields[$fieldName];
    }

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }
    public function offsetSet($offset, $value)
    {
        $this->fields[$offset] = $value;
    }
    public function offsetUnset($offset): void
    {
        unset($this->fields[$offset]);
    }
    public function toArray(): array
    {
        return $this->fields;
    }
    public function jsonSerialize()
    {
        return [
            'type' => (string)$this->recordType,
            'fields' => $this->toArray()
        ];
    }
}
