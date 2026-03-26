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
import{CreateMLCEngine as u}from"@mlc-ai/web-llm";class i{constructor(){this.engine=null,this.currentModelId=null}async ensureLoaded(n,t){this.engine!==null&&this.currentModelId===n||(this.engine=await u(n,{initProgressCallback:s=>{t(s.text)}}),this.currentModelId=n)}async chat(n,t,s){if(this.engine===null)throw new Error("BrowserLlmEngine: engine not loaded");const l=[...n],a=t.length>0?t:void 0;for(;;){const r=(await this.engine.chat.completions.create({messages:l,tools:a,stream:!1})).choices[0],e=r.message;if(l.push({role:"assistant",content:e.content??"",tool_calls:e.tool_calls}),r.finish_reason!=="tool_calls"||!e.tool_calls||e.tool_calls.length===0)return e.content??"";for(const o of e.tool_calls){let c={};try{c=JSON.parse(o.function.arguments)}catch{}const h=await s(o.function.name,o.id,c);l.push({role:"tool",tool_call_id:o.id,content:h})}}}}const g=new i;export{i as BrowserLlmEngine,g as browserLlmEngine};
