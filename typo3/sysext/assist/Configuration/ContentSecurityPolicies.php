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

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Type\Map;

/**
 * Extends the backend Content-Security-Policy to allow @mlc-ai/web-llm to download
 * model weights from HuggingFace CDN and run inference via WASM web workers.
 */
return Map::fromEntries([
    Scope::backend(),
    new MutationCollection(
        // Allow WebLLM to download model weights and config from HuggingFace CDN
        new Mutation(
            MutationMode::Extend,
            Directive::ConnectSrc,
            new UriValue('https://huggingface.co/mlc-ai/'),
            new UriValue('https://*.hf.co/'),
            new UriValue('https://raw.githubusercontent.com/mlc-ai/'),
        ),
        // Allow WASM execution (WebLLM's inference engine is compiled to WASM)
        new Mutation(MutationMode::Extend, Directive::ScriptSrc, SourceKeyword::wasmUnsafeEval),
        // Allow web workers created from blob: URLs (WebLLM spawns blob workers for inference)
        new Mutation(MutationMode::Extend, Directive::WorkerSrc, SourceScheme::blob),
    ),
]);
