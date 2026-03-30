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
import{html as n}from"lit";import o,{Positions as d,Sizes as l}from"@typo3/backend/modal.js";import{SeverityEnum as c}from"@typo3/backend/enum/severity.js";const i=async t=>{t.additionalModule&&import(t.additionalModule+".js"),await import("@typo3/assist/element/chat-element.js");const{default:s}=await import("~labels/"+t.labelDomain);o.advanced({title:s.get("chat.title"),additionalCssClasses:["assist-chat-modal"],severity:c.notice,size:l.large,position:d.bottom,content:n`<typo3-assist-chat-element .subject=${t.subject} .assistant=${t.assistant} .labels=${s} .input=${t.input??"optional"}></typo3-assist-chat-element>`,staticBackdrop:!0,hideHeader:!0})};document.addEventListener("click",t=>{const s=t.target;if(!s)return;const a=s.closest(".t3js-assist-trigger-item[data-assistant-identifier]");if(!a)return;t.preventDefault();const e={additionalModule:a.dataset.assistantModule,subject:a.dataset.assistantSubject,assistant:a.dataset.assistantIdentifier,labelDomain:a.dataset.assistantLabelDomain,input:a.dataset.assistantInput};i(e)});export{i as openAssistModal};
