# TYPO3 AI Assistant

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
    typo3-a11y:
      model: gemma-3-4b-it-qat@symfony/ai-lm-studio-platform
    typo3-inline-chat:
      model: gpt-oss-120b@mittwald/symfony-ai-platform```
