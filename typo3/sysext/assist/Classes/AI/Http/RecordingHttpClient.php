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
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * Wraps any Symfony HTTP client and logs outgoing requests and incoming
 * responses at debug level. Sensitive auth headers are stripped from logs.
 *
 * @internal
 */
final class RecordingHttpClient implements HttpClientInterface
{
    private HttpClientInterface $client;

    public function __construct(
        private readonly LoggerInterface $logger,
        ?HttpClientInterface $client = null,
    ) {
        $this->client = $client ?? HttpClient::create();
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $body = isset($options['json'])
            ? json_encode($options['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : ($options['body'] ?? '');

        $this->logger->debug(sprintf(
            "%s %s\n\n%s",
            $method,
            $url,
            $body,
        ));

        return new RecordingResponse(
            $this->client->request($method, $url, $options),
            $this->logger,
        );
    }

    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        // AsyncResponse keys streamed chunks by the exact response object it stored.
        // We must unwrap RecordingResponse → inner before the curl layer sees it,
        // but restore the RecordingResponse as the key on the way out so the
        // SplObjectStorage lookup in AsyncResponse::stream() still finds its entry.
        $map = new \SplObjectStorage();

        if ($responses instanceof RecordingResponse) {
            $map[$responses->unwrap()] = $responses;
            $responses = $responses->unwrap();
        } else {
            $unwrapped = [];
            foreach ($responses as $r) {
                $inner = $r instanceof RecordingResponse ? $r->unwrap() : $r;
                if ($r instanceof RecordingResponse) {
                    $map[$inner] = $r;
                }
                $unwrapped[] = $inner;
            }
            $responses = $unwrapped;
        }

        $innerStream = $this->client->stream($responses, $timeout);

        if ($map->count() === 0) {
            return $innerStream;
        }

        return new class ($innerStream, $map) implements ResponseStreamInterface {
            public function __construct(
                private readonly ResponseStreamInterface $inner,
                private readonly \SplObjectStorage $map,
            ) {}

            public function current(): ChunkInterface
            {
                return $this->inner->current();
            }

            public function key(): ResponseInterface
            {
                $key = $this->inner->key();
                return $this->map->offsetExists($key) ? $this->map[$key] : $key;
            }

            public function next(): void
            {
                $this->inner->next();
            }

            public function rewind(): void
            {
                $this->inner->rewind();
            }

            public function valid(): bool
            {
                return $this->inner->valid();
            }
        };
    }

    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->client = $this->client->withOptions($options);
        return $clone;
    }
}
