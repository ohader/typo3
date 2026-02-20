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
import{LitElement as b,css as d,html as f}from"lit";import{property as a,customElement as u}from"lit/decorators.js";import"@typo3/backend/element/icon-element.js";var p=function(i,e,r,n){var s=arguments.length,t=s<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,r):n,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(i,e,r,n);else for(var c=i.length-1;c>=0;c--)(l=i[c])&&(t=(s<3?l(t):s>3?l(e,r,t):l(e,r))||t);return s>3&&t&&Object.defineProperty(e,r,t),t};let o=class extends b{constructor(){super(...arguments),this.triggerResource="",this.triggerComponent="",this.label="Assist"}static{this.styles=d`:host{display:inline-block}:host([hidden]){display:none}.btn{display:inline-flex;align-items:center;gap:.375rem;padding:.25rem .625rem;border:1px solid var(--typo3-btn-border-color,#adb5bd);border-radius:3px;background:var(--typo3-btn-bg,#fff);color:var(--typo3-btn-color,inherit);font-size:.8125rem;font-family:inherit;line-height:1.5;cursor:pointer;white-space:nowrap}.btn:hover:not(:disabled){background:var(--typo3-btn-hover-bg,#f8f9fa)}.btn:focus-visible{outline:2px solid var(--typo3-color-primary,#007bff);outline-offset:2px}`}render(){return f`<button type=button class=btn @click=${this.handleClick}><typo3-backend-icon identifier=module-assist size=small></typo3-backend-icon>${this.label}</button>`}handleClick(){console.log("assist")}};p([a({type:String,attribute:"trigger-resource"})],o.prototype,"triggerResource",void 0),p([a({type:String,attribute:"trigger-component"})],o.prototype,"triggerComponent",void 0),p([a({type:String})],o.prototype,"label",void 0),o=p([u("typo3-assist-action")],o);export{o as AssistActionElement};
