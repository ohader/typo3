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
import l from"@typo3/core/ajax/ajax-request.js";import r from"@typo3/backend/notification.js";class m{constructor(){const e=document.querySelector("[data-assistant-management]");this.siteIdentifier=e?.dataset.siteIdentifier??"",this.assistantModels=JSON.parse(e?.dataset.assistantModels??"{}"),this.siteIdentifier&&document.querySelectorAll('select[data-action="select-model"]').forEach(t=>{const a=t,s=a.dataset.assistantIdentifier??"";s&&(this.loadMatchingModels(a,s),a.addEventListener("change",()=>{this.updateAssistantModel(s,a.value)}))})}async loadMatchingModels(e,t){try{const c=(await(await new l(TYPO3.settings.ajaxUrls.assist_assistant_get_matching_models).withQueryArguments({siteIdentifier:this.siteIdentifier,assistantIdentifier:t}).get()).resolve()).models??[];e.innerHTML="";const n=document.createElement("option");n.value="",n.textContent="\u2014 None \u2014",e.appendChild(n);for(const i of c){const o=document.createElement("option");o.value=i.identifier,o.textContent=i.model+" ("+i.platform+")",e.appendChild(o)}const d=this.assistantModels[t]??"";d&&(e.value=d)}catch{e.innerHTML='<option value="">Failed to load models</option>'}}async updateAssistantModel(e,t){try{const s=await(await new l(TYPO3.settings.ajaxUrls.assist_assistant_update_model).post({siteIdentifier:this.siteIdentifier,assistantIdentifier:e,model:t})).resolve();s.success?(this.assistantModels[e]=t,r.success("Model updated","Assistant model preference has been saved.",3)):r.error("Error",s.error||"Failed to save model preference.")}catch{r.error("Error","Failed to save model preference.")}}}var p=new m;export{p as default};
