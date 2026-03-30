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
import{property as f,customElement as u}from"lit/decorators.js";import{LitElement as a,html as c}from"lit";var p=function(s,t,r,i){var l=arguments.length,e=l<3?t:i===null?i=Object.getOwnPropertyDescriptor(t,r):i,n;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(s,t,r,i);else for(var m=s.length-1;m>=0;m--)(n=s[m])&&(e=(l<3?n(e):l>3?n(t,r,e):n(t,r))||e);return l>3&&e&&Object.defineProperty(t,r,e),e};let o=class extends a{constructor(){super(...arguments),this.items=[]}createRenderRoot(){return this}render(){return c`<ul class=assist-chat__list>${this.items.map(t=>c`<li class=assist-chat__list-item>${t}</li>`)}</ul>`}};p([f({type:Array})],o.prototype,"items",void 0),o=p([u("typo3-assist-list-element")],o);export{o as ListElement};
