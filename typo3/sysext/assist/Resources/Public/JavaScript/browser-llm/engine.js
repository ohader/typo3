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
import{WebWorkerMLCEngine as w}from"@mlc-ai/web-llm";class g{constructor(){this.worker=null,this.engine=null,this.currentModelId=null}async ensureLoaded(o,s){this.engine!==null&&this.currentModelId===o||(this.engine===null&&(this.worker=new Worker(new URL("./worker.js",import.meta.url),{type:"module"}),this.engine=new w(this.worker)),this.engine.setInitProgressCallback(i=>s(i.text)),await this.engine.reload(o),this.currentModelId=o)}async chat(o,s,i,c=!1,a){if(this.engine===null)throw new Error("BrowserLlmEngine: engine not loaded");const n=[...o];if(c){const r=n.findLastIndex(e=>e.role==="user");if(r!==-1){const e=n[r];typeof e.content=="string"&&(n[r]={...e,content:e.content+`
/no_think`})}}const f=s.length>0?s:void 0,d=a!=null?{type:"json_object",schema:JSON.stringify(a)}:void 0;for(;;){const e=(await this.engine.chat.completions.create({messages:n,tools:f,stream:!1,response_format:d})).choices[0],t=e.message,h=c?(t.content??"").replace(/<think>[\s\S]*?<\/think>/g,"").trim():t.content??"";if(n.push({role:"assistant",content:h,tool_calls:t.tool_calls}),e.finish_reason!=="tool_calls"||!t.tool_calls||t.tool_calls.length===0)return h;for(const l of t.tool_calls){let u={};try{u=JSON.parse(l.function.arguments)}catch{}const m=await i(l.function.name,l.id,u);n.push({role:"tool",tool_call_id:l.id,content:m})}}}}const p=new g;export{g as BrowserLlmEngine,p as browserLlmEngine};
