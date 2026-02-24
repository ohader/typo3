# TYPO3 AI Assistant

## Assistant Client Request/Response Protocol

### Overview

Assistants communicate with the backend via a single Ajax endpoint (`/ajax/assist/assistant/gate-client-request`).
All payloads are JSON. The request carries structured params in the body and control headers on the HTTP request.
The response always has the same three-key envelope.

### Request

| Part | How | Purpose |
|------|-----|---------|
| Body (`application/json`) | `{"identifier": "...", ...params}` | `identifier` selects the assistant; remaining keys are passed as `AssistantRequest::$params` |
| `x-typo3-assist-progress` header | UUID string | When present, continues an existing progress session; absent on the first call |

`AssistantRequest` is built by `AssistantRequest::fromServerRequest()`, which decodes the body and collects all `x-typo3-*` request headers.

### Response

Every `handleClientRequest()` implementation returns an `AssistantResponse` value object.
`AssistantAjaxController` calls `->toResponse()` to produce the final `JsonResponse`.

**Envelope (always present):**

```json
{
  "results": [...],
  "steps":   [...],
  "progress": { "uuid": "<uuid>" } | null
}
```

| Field | Type | Description |
|-------|------|-------------|
| `results` | array | AI result values. `ResultInterface` items serialized via `getContent()`; `QuestionInterface` items via `jsonSerialize()` |
| `steps` | array | `Step` objects (each implements `JsonSerializable`). Describes the task plan or current state |
| `progress` | object \| null | `{"uuid": "<uuid>"}` when a `Progress` record is attached; `null` otherwise |

### Progress Flow (multi-step assistants)

Some assistants (e.g. `MediaClassificationAssistant`) use a progress session to track work across multiple requests:

```
Client                                    Server
  |                                         |
  |-- POST /gate-client-request ----------->|
  |   body: {identifier: "...", ...}        |  no x-typo3-assist-progress header
  |   (no progress header)                  |
  |                                         |-- initializeProgress()
  |<-- {results:[], steps:[...], ----------|   returns steps (task plan)
  |     progress: null}                     |
  |                                         |
  |   [client stores progress UUID          |
  |    obtained from the agent call]        |
  |                                         |
  |-- POST /gate-client-request ----------->|
  |   x-typo3-assist-progress: <uuid>       |  progress header present
  |   body: {identifier: "...",             |
  |          steps: [...]}                  |
  |                                         |-- continueProgress()
  |<-- {results:[], steps:[...], ----------|   loads Progress from DB,
  |     progress: {uuid: "<uuid>"}}         |   returns updated steps
  |                                         |
  |   [repeat until done]                   |
```

**Steps** are serialized `Step` objects with fields: `identifier`, `description`, `subject`, `subs` (nested steps), `done`.

The progress `uuid` in the response body comes from the persisted `Progress` domain object. The UUID for a new session is recorded by `ProgressRecorder` during the agent call (not by `handleClientRequest` directly).

## Configuration

### Platform Configuration

Platforms can be configured via the site configuration module.
It is possible to define a specific site configuration as default,
in case there is no other more specific configuration for other sites.

The relevant part in ´sites/my-site/config.yaml` looks like this:

```yaml
assist:
  default: true
  platforms:
    - enabled: true
      name: 'LM Studio'
      package: symfony/ai-lm-studio-platform
      options:
        baseUrl: 'http://127.0.0.1:1234/'
      authorization:
        type: bearer
        token: sk-...-...
      models:
        - gemma-3-4b-it-qat
        - openai/gpt-oss-20b
    - enabled: true
      name: Mittwald
      package: mittwald/symfony-ai-platform
      options:
        baseUrl: 'https://llm.aihosting.mittwald.de/'
      authorization:
        type: bearer
        token: sk-...-...
      models:
        - gpt-oss-120b
        - Qwen3-Embedding-8B
  assistants:
    typo3-assist-a11y:
      model: gemma-3-4b-it-qat@symfony/ai-lm-studio-platform
    typo3-assist-inline-chat:
      model: gpt-oss-120b@mittwald/symfony-ai-platform
```
