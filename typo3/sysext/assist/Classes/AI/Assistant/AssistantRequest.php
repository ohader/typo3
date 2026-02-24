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

namespace TYPO3\CMS\Assist\AI\Assistant;

use Psr\Http\Message\ServerRequestInterface;

final readonly class AssistantRequest
{
    public function __construct(
        public array $params = [],
        public array $headers = [],
    ) {}

    public static function fromServerRequest(ServerRequestInterface $request): self
    {
        $params = json_decode((string)$request->getBody(), true) ?? [];
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            if (preg_match('/^x-typo3-/i', $name)) {
                $headers[$name] = $values[0] ?? '';
            }
        }
        return new self($params, $headers);
    }
}
