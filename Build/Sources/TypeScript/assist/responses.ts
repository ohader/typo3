export interface AssistantServerResponseProgress {
  type: 'progress:start',
  progress: {
    uuid: string
  }
}

export interface AssistantServerResponseResult {
  type: 'result',
  timestamp: string,
  results: AssistantServerResponseResultItem[]
}

export interface AssistantServerResponseResultItem {
  type: 'markdown'|'text'|'html',
  content: string
}
