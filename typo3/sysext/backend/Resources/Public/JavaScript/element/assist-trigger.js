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
import{customElement as p}from"lit/decorators.js";import{html as d}from"lit";import{PseudoButtonLitElement as f}from"@typo3/backend/element/pseudo-button.js";import u,{Positions as g,Sizes as v}from"@typo3/backend/modal.js";import{SeverityEnum as h}from"@typo3/backend/enum/severity.js";var y=function(t,e,i,r){var a=arguments.length,s=a<3?e:r===null?r=Object.getOwnPropertyDescriptor(e,i):r,o;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")s=Reflect.decorate(t,e,i,r);else for(var n=t.length-1;n>=0;n--)(o=t[n])&&(s=(a<3?o(s):a>3?o(e,i,s):o(e,i))||s);return a>3&&s&&Object.defineProperty(e,i,s),s};const b={meta:"Generate Meta Description",media:"Generate Media",alt:"Generate Image Alternative Text"},m=t=>t==="media"||t==="alt"?t:"meta",c=async t=>{await import("@typo3/backend/element/assist-chat-element.js"),u.advanced({title:b[t],additionalCssClasses:["assist-chat-modal"],severity:h.notice,size:v.large,position:g.bottom,content:d`<typo3-assist-chat-element template=${t}></typo3-assist-chat-element>`,staticBackdrop:!0,hideHeader:!0})};let l=class extends f{async buttonActivated(){await c(m(this.getAttribute("template")))}};l=y([p("typo3-assist-trigger")],l),document.addEventListener("click",t=>{const e=t.target;if(!e)return;const i=e.closest(".t3js-assist-trigger-item[data-assist-template]");i&&(t.preventDefault(),c(m(i.dataset.assistTemplate??null)))});export{l as AssistTrigger};
