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
import{customElement as p}from"lit/decorators.js";import{html as u}from"lit";import{PseudoButtonLitElement as d}from"@typo3/backend/element/pseudo-button.js";import f,{Positions as v,Sizes as g}from"@typo3/backend/modal.js";import{SeverityEnum as h}from"@typo3/backend/enum/severity.js";var b=function(t,e,i,a){var n=arguments.length,o=n<3?e:a===null?a=Object.getOwnPropertyDescriptor(e,i):a,s;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(t,e,i,a);else for(var r=t.length-1;r>=0;r--)(s=t[r])&&(o=(n<3?s(o):n>3?s(e,i,o):s(e,i))||o);return n>3&&o&&Object.defineProperty(e,i,o),o};const y={meta:"Generate Meta Description",media:"Generate Media",alt:"Generate Image Alternative Text"},c=t=>t==="media"||t==="alt"?t:"meta",m=async t=>{await import("@typo3/assist/element/chat-element.js"),f.advanced({title:y[t],additionalCssClasses:["assist-chat-modal"],severity:h.notice,size:g.large,position:v.bottom,content:u`<typo3-assist-chat-element template=${t}></typo3-assist-chat-element>`,staticBackdrop:!0,hideHeader:!0})};let l=class extends d{async buttonActivated(){await m(c(this.getAttribute("template")))}};l=b([p("typo3-assist-action-button")],l),document.addEventListener("click",t=>{const e=t.target;if(!e)return;const i=e.closest(".t3js-assist-trigger-item[data-assist-template]");i&&(t.preventDefault(),m(c(i.dataset.assistTemplate??null)))});export{l as ActionButton};
