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
import{property as d,state as h,customElement as f}from"lit/decorators.js";import{LitElement as m,html as u,nothing as p}from"lit";var r=function(l,e,t,n){var c=arguments.length,i=c<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,t):n,o;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")i=Reflect.decorate(l,e,t,n);else for(var a=l.length-1;a>=0;a--)(o=l[a])&&(i=(c<3?o(i):c>3?o(e,t,i):o(e,t))||i);return c>3&&i&&Object.defineProperty(e,t,i),i};let s=class extends m{constructor(){super(...arguments),this.key="",this.text="",this.items=[],this.selectedIdentifier=null}createRenderRoot(){return this}render(){return u`${this.text?u`<p class=assist-chat__text>${this.text}</p>`:p}<ul class=assist-chat__quick-actions>${this.items.map(e=>u`<li><a href=# class="assist-chat__quick-action ${this.selectedIdentifier!==null?"disabled":""}" aria-disabled=${this.selectedIdentifier!==null?"true":p} @click=${t=>this.handleSelection(t,e)}>${e.text}</a></li>`)}</ul>`}handleSelection(e,t){if(e.preventDefault(),this.selectedIdentifier!==null)return;this.selectedIdentifier=t.identifier;const n=()=>{this.selectedIdentifier=null};this.dispatchEvent(new CustomEvent("typo3-assist-quick-action-select",{detail:{key:this.key,identifier:t.identifier,text:t.text,recover:n},bubbles:!0}))}};r([d({type:String})],s.prototype,"key",void 0),r([d({type:String})],s.prototype,"text",void 0),r([d({type:Array})],s.prototype,"items",void 0),r([h()],s.prototype,"selectedIdentifier",void 0),s=r([f("typo3-assist-quick-actions-element")],s);export{s as QuickActionsElement};
