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

namespace TYPO3\CMS\Assist\Service;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Assist\Domain\Dto\PageSubject;
use TYPO3\CMS\Assist\Domain\Dto\SubjectInterface;
use TYPO3\CMS\Backend\Routing\Route;

/**
 * @internal
 */
final readonly class SubjectResolver
{
    public function resolveFromRequest(ServerRequestInterface $request): ?SubjectInterface
    {
        $route = $request->getAttribute('route');
        if (!$route instanceof Route) {
            return null;
        }
        // @todo this might also be usable with a path prefix, e.g. for `/module/web/*`
        return match ($route->getPath()) {
            '/module/web/layout' => $this->resolvePageSubject($request),
            default => null,
        };
    }

    private function resolvePageSubject(ServerRequestInterface $request): ?PageSubject
    {
        $id = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? null;
        if (!is_numeric($id) || (int)$id === 0) {
            return null;
        }
        $moduleData = $request->getAttribute('moduleData');
        $languageId = $moduleData !== null ? (int)($moduleData->get('language') ?? 0) : 0;
        $workspaceId = (int)($GLOBALS['BE_USER']->workspace ?? 0);
        return new PageSubject(uid: (int)$id, languageId: $languageId, workspaceId: $workspaceId);
    }
}
