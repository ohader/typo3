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
import{LitElement as m,html as a}from"lit";import{property as u,customElement as b}from"lit/decorators.js";import"@typo3/backend/element/icon-element.js";var c=function(n,e,r,i){var s=arguments.length,t=s<3?e:i===null?i=Object.getOwnPropertyDescriptor(e,r):i,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(n,e,r,i);else for(var p=n.length-1;p>=0;p--)(l=n[p])&&(t=(s<3?l(t):s>3?l(e,r,t):l(e,r))||t);return s>3&&t&&Object.defineProperty(e,r,t),t};let o=class extends m{constructor(){super(...arguments),this.triggerResource="",this.triggerComponent="",this.label="Assist"}createRenderRoot(){return this}render(){return a`<button type=button class="btn btn-info btn-sm" @click=${this.handleClick}><typo3-backend-icon identifier=module-assist size=small></typo3-backend-icon>${this.label}</button>`}handleClick(){console.log("assist")}};c([u({type:String,attribute:"trigger-resource"})],o.prototype,"triggerResource",void 0),c([u({type:String,attribute:"trigger-component"})],o.prototype,"triggerComponent",void 0),c([u({type:String})],o.prototype,"label",void 0),o=c([b("typo3-assist-action")],o);export{o as AssistActionElement};
