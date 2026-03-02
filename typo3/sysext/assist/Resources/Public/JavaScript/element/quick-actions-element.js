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
import{property as a,customElement as h}from"lit/decorators.js";import{LitElement as f,html as u}from"lit";var l=function(s,t,n,o){var r=arguments.length,e=r<3?t:o===null?o=Object.getOwnPropertyDescriptor(t,n):o,c;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(s,t,n,o);else for(var p=s.length-1;p>=0;p--)(c=s[p])&&(e=(r<3?c(e):r>3?c(t,n,e):c(t,n))||e);return r>3&&e&&Object.defineProperty(t,n,e),e};let i=class extends f{constructor(){super(...arguments),this.key="",this.text="",this.items=[]}createRenderRoot(){return this}render(){return u`<ul class=assist-chat__quick-actions>${this.items.map(t=>u`<li><a href=# class=assist-chat__quick-action @click=${()=>this.handleSelection(t)}>${t.text}</a></li>`)}</ul>`}handleSelection(t){this.dispatchEvent(new CustomEvent("typo3-assist-quick-action-select",{detail:{key:this.key,identifier:t.identifier,text:t.text},bubbles:!0}))}};l([a({type:String})],i.prototype,"key",void 0),l([a({type:String})],i.prototype,"text",void 0),l([a({type:Array})],i.prototype,"items",void 0),i=l([h("typo3-assist-quick-actions-element")],i);export{i as QuickActionsElement};
