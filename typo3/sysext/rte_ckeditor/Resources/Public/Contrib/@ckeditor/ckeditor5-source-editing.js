import{Plugin as e,PendingActions as t}from"@ckeditor/ckeditor5-core";import{ButtonView as i}from"@ckeditor/ckeditor5-ui";import{ElementReplacer as o,createElement as n}from"@ckeditor/ckeditor5-utils";
/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */function a(e){const t=[{name:"address",isVoid:!1},{name:"article",isVoid:!1},{name:"aside",isVoid:!1},{name:"blockquote",isVoid:!1},{name:"details",isVoid:!1},{name:"dialog",isVoid:!1},{name:"dd",isVoid:!1},{name:"div",isVoid:!1},{name:"dl",isVoid:!1},{name:"dt",isVoid:!1},{name:"fieldset",isVoid:!1},{name:"figcaption",isVoid:!1},{name:"figure",isVoid:!1},{name:"footer",isVoid:!1},{name:"form",isVoid:!1},{name:"h1",isVoid:!1},{name:"h2",isVoid:!1},{name:"h3",isVoid:!1},{name:"h4",isVoid:!1},{name:"h5",isVoid:!1},{name:"h6",isVoid:!1},{name:"header",isVoid:!1},{name:"hgroup",isVoid:!1},{name:"hr",isVoid:!0},{name:"li",isVoid:!1},{name:"main",isVoid:!1},{name:"nav",isVoid:!1},{name:"ol",isVoid:!1},{name:"p",isVoid:!1},{name:"section",isVoid:!1},{name:"table",isVoid:!1},{name:"tbody",isVoid:!1},{name:"td",isVoid:!1},{name:"th",isVoid:!1},{name:"thead",isVoid:!1},{name:"tr",isVoid:!1},{name:"ul",isVoid:!1}],i=t.map((e=>e.name)).join("|"),o=e.replace(new RegExp(`</?(${i})( .*?)?>`,"g"),"\n$&\n").replace(/<br[^>]*>/g,"$&\n").split("\n");let n=0;return o.filter((e=>e.length)).map((e=>function(e,t){return t.some((t=>!t.isVoid&&!!new RegExp(`<${t.name}( .*?)?>`).test(e)))}(e,t)?s(e,n++):function(e,t){return t.some((t=>new RegExp(`</${t.name}>`).test(e)))}(e,t)?s(e,--n):s(e,n))).join("\n")}function s(e,t,i="    "){return`${i.repeat(Math.max(0,t))}${e}`}!function(e,{insertAt:t}={}){if(!e||"undefined"==typeof document)return;const i=document.head||document.getElementsByTagName("head")[0],o=document.createElement("style");o.type="text/css",window.litNonce&&o.setAttribute("nonce",window.litNonce),"top"===t&&i.firstChild?i.insertBefore(o,i.firstChild):i.appendChild(o),o.styleSheet?o.styleSheet.cssText=e:o.appendChild(document.createTextNode(e))}('.ck-source-editing-area{overflow:hidden;position:relative}.ck-source-editing-area textarea,.ck-source-editing-area:after{border:1px solid transparent;font-family:monospace;font-size:var(--ck-font-size-normal);line-height:var(--ck-line-height-base);margin:0;padding:var(--ck-spacing-large);white-space:pre-wrap}.ck-source-editing-area:after{content:attr(data-value) " ";display:block;visibility:hidden}.ck-source-editing-area textarea{border-color:var(--ck-color-base-border);border-radius:0;box-sizing:border-box;height:100%;outline:none;overflow:hidden;position:absolute;resize:none;width:100%}.ck-rounded-corners .ck-source-editing-area textarea,.ck-source-editing-area textarea.ck-rounded-corners{border-radius:var(--ck-border-radius);border-top-left-radius:0;border-top-right-radius:0}.ck-source-editing-area textarea:not([readonly]):focus{border:var(--ck-focus-ring);box-shadow:var(--ck-inner-shadow),0 0;outline:none}');
/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */
const d="SourceEditingMode";class r extends e{static get pluginName(){return"SourceEditing"}static get requires(){return[t]}constructor(e){super(e),this.set("isSourceEditingMode",!1),this._elementReplacer=new o,this._replacedRoots=new Map,this._dataFromRoots=new Map}init(){const e=this.editor,o=e.t;e.ui.componentFactory.add("sourceEditing",(n=>{const a=new i(n);return a.set({label:o("Source"),icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m12.5 0 5 4.5v15.003h-16V0h11zM3 1.5v3.25l-1.497 1-.003 8 1.5 1v3.254L7.685 18l-.001 1.504H17.5V8.002L16 9.428l-.004-4.22-4.222-3.692L3 1.5z"/><path d="M4.06 6.64a.75.75 0 0 1 .958 1.15l-.085.07L2.29 9.75l2.646 1.89c.302.216.4.62.232.951l-.058.095a.75.75 0 0 1-.951.232l-.095-.058-3.5-2.5V9.14l3.496-2.5zm4.194 6.22a.75.75 0 0 1-.958-1.149l.085-.07 2.643-1.89-2.646-1.89a.75.75 0 0 1-.232-.952l.058-.095a.75.75 0 0 1 .95-.232l.096.058 3.5 2.5v1.22l-3.496 2.5zm7.644-.836 2.122 2.122-5.825 5.809-2.125-.005.003-2.116zm2.539-1.847 1.414 1.414a.5.5 0 0 1 0 .707l-1.06 1.06-2.122-2.12 1.061-1.061a.5.5 0 0 1 .707 0z"/></svg>',tooltip:!0,withText:!0,class:"ck-source-editing-button"}),a.bind("isOn").to(this,"isSourceEditingMode"),a.bind("isEnabled").to(this,"isEnabled",e,"isReadOnly",e.plugins.get(t),"hasAny",((e,t,i)=>!!e&&(!t&&!i))),this.listenTo(a,"execute",(()=>{this.isSourceEditingMode=!this.isSourceEditingMode})),a})),this._isAllowedToHandleSourceEditingMode()&&(this.on("change:isSourceEditingMode",((e,t,i)=>{i?(this._showSourceEditing(),this._disableCommands()):(this._hideSourceEditing(),this._enableCommands())})),this.on("change:isEnabled",((e,t,i)=>this._handleReadOnlyMode(!i))),this.listenTo(e,"change:isReadOnly",((e,t,i)=>this._handleReadOnlyMode(i)))),e.data.on("get",(()=>{this.isSourceEditingMode&&this.updateEditorData()}),{priority:"high"})}afterInit(){const e=this.editor;["RealTimeCollaborativeEditing","CommentsEditing","TrackChangesEditing","RevisionHistory"].some((t=>e.plugins.has(t)))&&console.warn("You initialized the editor with the source editing feature and at least one of the collaboration features. Please be advised that the source editing feature may not work, and be careful when editing document source that contains markers created by the collaboration features."),e.plugins.has("RestrictedEditingModeEditing")&&console.warn("You initialized the editor with the source editing feature and restricted editing feature. Please be advised that the source editing feature may not work, and be careful when editing document source that contains markers created by the restricted editing feature.")}updateEditorData(){const e=this.editor,t={};for(const[e,i]of this._replacedRoots){const o=this._dataFromRoots.get(e),n=i.dataset.value;o!==n&&(t[e]=n,this._dataFromRoots.set(e,n))}Object.keys(t).length&&e.data.set(t,{batchType:{isUndoable:!0}})}_showSourceEditing(){const e=this.editor,t=e.editing.view,i=e.model;i.change((e=>{e.setSelection(null),e.removeSelectionAttribute(i.document.selection.getAttributeKeys())}));for(const[i,o]of t.domRoots){const a=c(e.data.get({rootName:i})),s=n(o.ownerDocument,"textarea",{rows:"1","aria-label":"Source code editing area"}),d=n(o.ownerDocument,"div",{class:"ck-source-editing-area","data-value":a},[s]);s.value=a,s.setSelectionRange(0,0),s.addEventListener("input",(()=>{d.dataset.value=s.value,e.ui.update()})),t.change((e=>{const o=t.document.getRoot(i);e.addClass("ck-hidden",o)})),e.ui.setEditableElement("sourceEditing:"+i,s),this._replacedRoots.set(i,d),this._elementReplacer.replace(o,d),this._dataFromRoots.set(i,a)}this._focusSourceEditing()}_hideSourceEditing(){const e=this.editor.editing.view;this.updateEditorData(),e.change((t=>{for(const[i]of this._replacedRoots)t.removeClass("ck-hidden",e.document.getRoot(i))})),this._elementReplacer.restore(),this._replacedRoots.clear(),this._dataFromRoots.clear(),e.focus()}_focusSourceEditing(){const e=this.editor,[t]=this._replacedRoots.values(),i=t.querySelector("textarea");e.editing.view.document.isFocused=!1,i.focus()}_disableCommands(){const e=this.editor;for(const t of e.commands.commands())t.forceDisabled(d)}_enableCommands(){const e=this.editor;for(const t of e.commands.commands())t.clearForceDisabled(d)}_handleReadOnlyMode(e){if(this.isSourceEditingMode)for(const[,t]of this._replacedRoots)t.querySelector("textarea").readOnly=e}_isAllowedToHandleSourceEditingMode(){const e=this.editor.ui.view.editable;return e&&!e.hasExternalElement}}function c(e){return function(e){return e.startsWith("<")}(e)?a(e):e}export{r as SourceEditing};