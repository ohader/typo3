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
import{html as o}from"lit";import n,{Positions as d,Sizes as l}from"@typo3/backend/modal.js";import{SeverityEnum as r}from"@typo3/backend/enum/severity.js";const e=async t=>{t.additionalModule&&import(t.additionalModule+".js"),await import("@typo3/assist/element/chat-element.js");const{default:a}=await import("~labels/"+t.labelDomain);n.advanced({title:a.get("chat.title"),additionalCssClasses:["assist-chat-modal"],severity:r.notice,size:l.large,position:d.bottom,content:o`<typo3-assist-chat-element .subject=${t.subject} .assistant=${t.assistant} .labels=${a}></typo3-assist-chat-element>`,staticBackdrop:!0,hideHeader:!0})};document.addEventListener("click",t=>{const a=t.target;if(!a)return;const s=a.closest(".t3js-assist-trigger-item[data-assistant-identifier]");if(!s)return;t.preventDefault();const i={additionalModule:s.dataset.assistantModule,subject:s.dataset.assistantSubject,assistant:s.dataset.assistantIdentifier,labelDomain:s.dataset.assistantLabelDomain};e(i)});export{e as openAssistModal};
