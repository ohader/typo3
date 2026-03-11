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

namespace TYPO3\CMS\Assist\AI\Http;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Wraps a Symfony HTTP response and logs the status code and body when consumed.
 *
 * @internal
 */
final class RecordingResponse implements ResponseInterface
{
    public function __construct(
        private readonly ResponseInterface $inner,
        private readonly LoggerInterface $logger,
    ) {}

    public function getStatusCode(): int
    {
        return $this->inner->getStatusCode();
    }

    public function getHeaders(bool $throw = true): array
    {
        return $this->inner->getHeaders($throw);
    }

    public function getContent(bool $throw = true): string
    {
        $content = $this->inner->getContent($throw);
        $this->logger->debug(sprintf(
            "HTTP/%s %s\n\n%s",
            $this->inner->getInfo('http_version') ?? '1.1',
            $this->inner->getStatusCode(),
            $content,
        ));
        return $content;
    }

    public function toArray(bool $throw = true): array
    {
        $data = $this->inner->toArray($throw);
        $this->logger->debug(sprintf(
            "HTTP/%s %s\n\n%s",
            $this->inner->getInfo('http_version') ?? '1.1',
            $this->inner->getStatusCode(),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ));
        return $data;
    }

    public function cancel(): void
    {
        $this->inner->cancel();
    }

    public function getInfo(?string $type = null): mixed
    {
        return $this->inner->getInfo($type);
    }
}
