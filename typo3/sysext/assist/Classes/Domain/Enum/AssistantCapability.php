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

namespace TYPO3\CMS\Assist\Domain\Enum;

use Symfony\AI\Platform\Capability;

enum AssistantCapability: string
{
    case messages = 'messages';
    case embeddings = 'embeddings';
    case textToSpeech = 'textToSpeech';
    case textToImage = 'textToImage';
    case speechToText = 'speechToText';
    case imageToImage = 'imageToImage';

    case toolCalling = 'toolCalling';

    case inputAudio = 'inputAudio';
    case inputImage = 'inputImage';
    case inputPdf = 'inputPdf';

    case outputStreaming = 'outputStreaming';
    case outputStructured = 'outputStructured';

    /**
     * @return list<Capability>
     */
    public static function normalize(string|self|Capability $capability): array
    {
        if ($capability instanceof self) {
            return $capability->convertToCapabilities();
        }
        if ($capability instanceof Capability) {
            return [$capability];
        }
        $target = self::tryFrom($capability);
        if ($target === null) {
            throw new \LogicException('Unknown capability: ' . $capability, 1771059495);
        }
        return $target->convertToCapabilities();
    }

    /**
     * @return list<Capability>
     */
    public function convertToCapabilities(): array
    {
        return match ($this) {
            self::messages => [Capability::INPUT_MESSAGES, Capability::OUTPUT_TEXT],
            self::embeddings => [Capability::INPUT_TEXT, Capability::EMBEDDINGS],
            self::textToSpeech => [Capability::INPUT_TEXT, Capability::OUTPUT_AUDIO],
            self::textToImage => [Capability::INPUT_TEXT, Capability::OUTPUT_IMAGE],
            self::speechToText => [Capability::INPUT_AUDIO, Capability::OUTPUT_TEXT],
            self::imageToImage => [Capability::INPUT_IMAGE, Capability::OUTPUT_IMAGE, Capability::IMAGE_TO_IMAGE],
            self::toolCalling => [Capability::TOOL_CALLING],
            self::inputAudio => [Capability::INPUT_AUDIO],
            self::inputImage => [Capability::INPUT_IMAGE],
            self::inputPdf => [Capability::INPUT_PDF],
            self::outputStreaming => [Capability::OUTPUT_STREAMING],
            self::outputStructured => [Capability::OUTPUT_STRUCTURED],
        };
    }
}
