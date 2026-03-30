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

/**
 * A single message in the conversation.
 */
export interface ChatMessage {
  role: 'user' | 'assistant';
  content: string;
  timestamp?: string;
}

/**
 * A reference to a TCA field on a specific record.
 */
export interface TcaResource {
  type: 'TcaResource';
  tableName: string;
  identifier: number | string;
  propertyName: string;
}

/**
 * Context forwarded from the field wizard to the chat panel.
 */
export interface AssistContext {
  resource: TcaResource;
  currentValue?: string;
}
