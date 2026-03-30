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

namespace TYPO3\CMS\Assist\AI\Message;

use Symfony\AI\Platform\Result\ResultInterface;

/**
 * @implements \IteratorAggregate<int, ResultInterface>
 */
final class AgentResultBag implements \Countable, \IteratorAggregate
{
    /** @var list<ResultInterface> */
    private array $results;

    public function __construct(ResultInterface ...$results)
    {
        $this->results = array_values($results);
    }

    public function add(ResultInterface $result): void
    {
        $this->results[] = $result;
    }

    /**
     * @return list<ResultInterface>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function count(): int
    {
        return count($this->results);
    }

    /**
     * @return \ArrayIterator<int, ResultInterface>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->results);
    }
}
