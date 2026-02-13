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
    - name: LM Studio
      package: symfony/ai-lm-studio-platform
      enabled: true
      options:
        - baseUrl: 'http://localhost:1234/'
      authorization:
        type: bearer
        token: sk-test-1234
```
