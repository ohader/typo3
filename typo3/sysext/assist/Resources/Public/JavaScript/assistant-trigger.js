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
import{html as i}from"lit";import o,{Positions as n,Sizes as l}from"@typo3/backend/modal.js";import{SeverityEnum as d}from"@typo3/backend/enum/severity.js";const r=async t=>{await import("@typo3/assist/element/chat-element.js");const{default:s}=await import("~labels/"+t.labelDomain);o.advanced({title:s.get("chat.title"),additionalCssClasses:["assist-chat-modal"],severity:d.notice,size:l.large,position:n.bottom,content:i`<typo3-assist-chat-element .module=${t.module} .subject=${t.subject} .assistant=${t.assistant} .labels=${s}></typo3-assist-chat-element>`,staticBackdrop:!0,hideHeader:!0})};document.addEventListener("click",t=>{const s=t.target;if(!s)return;const a=s.closest(".t3js-assist-trigger-item[data-assistant-identifier]");if(!a)return;t.preventDefault();const e={module:a.dataset.assistantModule,subject:a.dataset.assistantSubject,assistant:a.dataset.assistantIdentifier,labelDomain:a.dataset.assistantLabelDomain};r(e)});
