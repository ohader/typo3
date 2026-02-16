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

use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\MessageInterface;
use TYPO3\CMS\Assist\AI\Platform\PlatformModel;

final class AgentInput implements AgentInputInterface
{
    private MessageBag $messageBag;

    public function __construct(
        public readonly PlatformModel $model,
        MessageInterface ...$messages,
    ) {
        $this->messageBag = new MessageBag(...$messages);
    }

    public function getModel(): PlatformModel
    {
        return $this->model;
    }

    public function add(MessageInterface $message): void
    {
        $this->messageBag->add($message);
    }

    public function getMessageBag(): MessageBag
    {
        return $this->messageBag;
    }
}
