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
import{property as h,customElement as d}from"lit/decorators.js";import{LitElement as f,html as l,nothing as u}from"lit";import m from"~labels/assist.elements";var c=function(i,t,o,n){var a=arguments.length,e=a<3?t:n===null?n=Object.getOwnPropertyDescriptor(t,o):n,r;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(i,t,o,n);else for(var p=i.length-1;p>=0;p--)(r=i[p])&&(e=(a<3?r(e):a>3?r(t,o,e):r(t,o))||e);return a>3&&e&&Object.defineProperty(t,o,e),e};let s=class extends f{constructor(){super(...arguments),this.text="",this.options=[]}createRenderRoot(){return this}render(){return l`<p class=assist-chat__text>${this.text}</p><div class=assist-chat__options>${this.options.map((t,o)=>l`<article class="panel panel-default assist-chat__option"><div class=panel-heading><h3 class="h5 assist-chat__option-title">Option ${this.indexToChar(o)} - ${t.text}</h3></div>${t.details?l`<div class="panel-body assist-chat__option-text">${t.details}</div>`:u}<div class="panel-footer assist-chat__option-actions"><button type=button class="assist-chat__option-action btn btn-default">${m.get("button.accept")}</button></div></article>`)}</div>`}indexToChar(t){return t<0||t>25?t.toString():String.fromCharCode(65+t)}};c([h({type:String})],s.prototype,"text",void 0),c([h({type:Array})],s.prototype,"options",void 0),s=c([d("typo3-assist-options-element")],s);export{s as OptionsElement};
