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
import{property as p,customElement as h}from"lit/decorators.js";import{LitElement as f,html as u,nothing as m}from"lit";var l=function(n,t,s,o){var r=arguments.length,e=r<3?t:o===null?o=Object.getOwnPropertyDescriptor(t,s):o,c;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(n,t,s,o);else for(var a=n.length-1;a>=0;a--)(c=n[a])&&(e=(r<3?c(e):r>3?c(t,s,e):c(t,s))||e);return r>3&&e&&Object.defineProperty(t,s,e),e};let i=class extends f{constructor(){super(...arguments),this.key="",this.text="",this.items=[]}createRenderRoot(){return this}render(){return u`${this.text?u`<p class=assist-chat__text>${this.text}</p>`:m}<ul class=assist-chat__quick-actions>${this.items.map(t=>u`<li><a href=# class=assist-chat__quick-action @click=${()=>this.handleSelection(t)}>${t.text}</a></li>`)}</ul>`}handleSelection(t){this.dispatchEvent(new CustomEvent("typo3-assist-quick-action-select",{detail:{key:this.key,identifier:t.identifier,text:t.text},bubbles:!0}))}};l([p({type:String})],i.prototype,"key",void 0),l([p({type:String})],i.prototype,"text",void 0),l([p({type:Array})],i.prototype,"items",void 0),i=l([h("typo3-assist-quick-actions-element")],i);export{i as QuickActionsElement};
