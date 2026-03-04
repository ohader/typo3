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
import{openAssistModal as i}from"@typo3/assist/assistant-trigger.js";class s{openAssistant(n,e,t){const a=JSON.stringify({kind:"tca",tableName:n,uid:e,propertyName:"",flexFormPath:null,types:null}),o={additionalModule:t.assistantModule??"",subject:a,assistant:t.assistantIdentifier??"",labelDomain:t.assistantLabelDomain??""};i(o)}}var l=new s;export{s as ContextMenuActions,l as default};
