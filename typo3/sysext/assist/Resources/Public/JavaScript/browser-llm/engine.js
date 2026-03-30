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
import{WebWorkerMLCEngine as w}from"@mlc-ai/web-llm";class f{constructor(){this.worker=null,this.engine=null,this.currentModelId=null}async ensureLoaded(o,s){this.engine!==null&&this.currentModelId===o||(this.engine===null&&(this.worker=new Worker(new URL("./worker.js",import.meta.url),{type:"module"}),this.engine=new w(this.worker)),this.engine.setInitProgressCallback(i=>s(i.text)),await this.engine.reload(o),this.currentModelId=o)}async chat(o,s,i,c=!1,h,a){if(this.engine===null)throw new Error("BrowserLlmEngine: engine not loaded");const t=[...o];if(c){const r=t.findLastIndex(e=>e.role==="user");if(r!==-1){const e=t[r];typeof e.content=="string"&&(t[r]={...e,content:e.content+`
/no_think`})}}const m=s.length>0?s:void 0,d=h!=null?{type:"json_object",schema:JSON.stringify(h)}:void 0;for(;;){const e=(await this.engine.chat.completions.create({messages:t,tools:m,stream:!1,response_format:d,temperature:a?.temperature,max_tokens:a?.max_tokens,repetition_penalty:a?.repetition_penalty})).choices[0],n=e.message,u=c?(n.content??"").replace(/<think>[\s\S]*?<\/think>/g,"").trim():n.content??"";if(t.push({role:"assistant",content:u,tool_calls:n.tool_calls}),e.finish_reason!=="tool_calls"||!n.tool_calls||n.tool_calls.length===0)return u;for(const l of n.tool_calls){let g={};try{g=JSON.parse(l.function.arguments)}catch{}const p=await i(l.function.name,l.id,g);t.push({role:"tool",tool_call_id:l.id,content:p})}}}}const _=new f;export{f as BrowserLlmEngine,_ as browserLlmEngine};
