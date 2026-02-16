# EXT:assist — TYPO3 AI Assistant

_Last updated: 2026-02-16_

Experimental TYPO3 system extension (v14.2) that integrates Symfony AI platform packages into the TYPO3 backend. Classes, interfaces, and configuration may change without notice.

Package: `typo3/cms-assist` | Extension key: `assist` | Namespace: `TYPO3\CMS\Assist\`

## Development & Tools

The extension directory is `typo3/sysext/assist/` (relative to the TYPO3 project root source).

`Build/Scripts/runTests.sh` requires Docker or podman.

### JavaScript Client Components

TypeScript is located in directory `Build/Sources/TypeScript/assist/`, compiled `.js` versions are copied to `typo3/sysext/assist/Resources/Public/JavaScript/`
TypeScript is compiled with `cd Build; nvm use; npm run build-js`
TypeScript watch mode for iterative development: `Build/Scripts/runTests.sh -s npm -- run watch:build`
TypeScript linting: `Build/Scripts/runTests.sh -s lintTypescript`

### PHP Tests (phpunit)

Functional tests are executed with `Build/Scripts/runTests.sh -s functional typo3/sysext/assist/Tests/Functional/`
Run a single test method: `Build/Scripts/runTests.sh -s functional -- --filter testMethodName typo3/sysext/assist/Tests/Functional/Domain/PlatformBridgeTest.php`
Only run tests when your changes affect testable code (e.g. PHP class logic, configuration loading). To save tokens, you may also delegate test execution to the developer.

### Coding Guidelines (CGL)

Check coding style: `Build/Scripts/runTests.sh -s cgl typo3/sysext/assist/`

## Architecture

Four-layer platform abstraction:

1. **Configuration** — nested `assist:` section in site YAML (`sites/<site>/config.yaml`)
2. **Domain** — immutable value objects (`Platform`, `Assistant`, `Authorization`)
3. **Bridge** — `PlatformBridge` dynamically instantiates Symfony AI classes via reflection on composer PSR-4 metadata
4. **Runtime** — live Symfony AI `PlatformInterface` and `ModelCatalogInterface` instances

Flow: `SiteConfig → AI\Platform\PlatformResolver → Domain\Model\Platform → AI\Platform\PlatformConnector → BeforeBuildPlatformBridgeEvent → AI\Platform\PlatformBridge → PlatformFactory/ModelCatalog`

## Directory structure

```
Classes/
  AI/
    Assistant/        AssistantInterface (marker), A11yAssistant, InlineChatAssistant
    Platform/         PlatformBridge, PlatformConnector, PlatformResolver,
                      PlatformReflector, PlatformModel, FilteredModelCatalog
  Backend/            PlatformItemsProcFunc (TCA items processor), PlatformTcaProvider
  Controller/
    Ajax/             PlatformAjaxController (connection test, model CRUD)
    Module/           AssistantController, PlatformController, GlossaryController, PromptController
  Domain/
    Enum/             AssistantCapability, AssistantMode, Availability, ProgressItemType
    Model/            Assistant, AssistantTrigger, Platform, Progress, ProgressItem
    Repository/       ProgressRepository
  Event/              BeforeBuildPlatformBridgeEvent
  EventListener/      PlatformBridgeBuilder (LM Studio model discovery),
                      SiteConfigurationAssistNormalizer (YAML ↔ TCA flattening)
  Exception/          Exception (base), PlatformNotAvailableException
  Service/            AssistantRegistry, PackageService
  ServiceProvider.php (DI factories, cache warmup, event wiring)
Configuration/
  Backend/            AjaxRoutes.php, Assistants.php, Modules.php
  SiteConfiguration/  site_assist_platform.php, Overrides/site.php
Tests/Functional/
  AssistBasedTestTrait.php
  Domain/             PackageServiceTest, PlatformResolverTest, PlatformBridgeTest
  Fixtures/           pages.csv
```

## Key classes

| Class | Role |
|---|---|
| `AI\Platform\PlatformResolver` | Site config → `Platform` domain objects. Determines `Availability` based on package presence + enabled flag. |
| `AI\Platform\PlatformConnector` | `Platform` → `PlatformBridge`. Extracts PSR-4 namespace from composer metadata, dispatches `BeforeBuildPlatformBridgeEvent`, constructs bridge. `@internal` |
| `AI\Platform\PlatformBridge` | Dynamically resolves `{namespace}PlatformFactory` and `{namespace}ModelCatalog` via reflection. Wraps catalog in `FilteredModelCatalog` when effective=true. `@internal` |
| `AI\Platform\PlatformReflector` | Uses PHP reflection to discover Symfony AI platform classes and their constructor parameters. `@internal` |
| `AI\Platform\PlatformModel` | Value object for `model@platform` identifier strings. |
| `AI\Platform\FilteredModelCatalog` | Decorator that filters a `ModelCatalogInterface` to a platform's model whitelist. `@internal` |
| `Service\PackageService` | Reads `vendor/composer/installed.json`. Finds packages by type (`symfony-ai-platform`). |
| `Service\AssistantRegistry` | Central registry of all assistants. Queryable by mode, capability, trigger type, record. |
| `Domain\Model\Platform` | `final readonly` value object: availability, name, package, options, authorization, models. |
| `Domain\Model\Assistant` | `final readonly` value object with `createFromConfiguration()` factory. `@internal` |
| `Domain\Model\AssistantTrigger` | Value object defining when an assistant triggers based on types, records, or components. `@internal` |
| `Domain\Enum\AssistantCapability` | String-backed enum. Maps TYPO3 capability names → Symfony AI `Capability` objects via `convertToCapabilities()`. |
| `Domain\Enum\Availability` | Enum: `enabled`, `disabled`, `unavailable` |
| `Domain\Enum\AssistantMode` | Enum: `module`, `inline` |
| `Event\BeforeBuildPlatformBridgeEvent` | Modify bridge options before construction (e.g. inject additional models). `@internal` |
| `EventListener\SiteConfigurationAssistNormalizer` | Bidirectional transform: nested `assist:` YAML ↔ flat `assistDefault`/`assistPlatforms`/`assistAssistants` TCA keys. |
| `ServiceProvider` | Registers `backend.assistants` ArrayObject factory, cache warmup, `AssistantRegistry` construction. |

## Extension points

### Registering assistants

Add entries in `Configuration/Backend/Assistants.php` (returns array keyed by identifier):

```php
return [
    'my-assistant' => [
        'mode' => 'module',           // or 'inline'
        'capabilities' => [AssistantCapability::messages, AssistantCapability::toolCalling],
        'handler' => MyAssistant::class, // must implement AssistantInterface
        'trigger' => [
            'types' => ['context'],
            'records' => ['pages'],
            'components' => [],
        ],
    ],
];
```

The handler class must implement `TYPO3\CMS\Assist\AI\Assistant\AssistantInterface`.

### Customizing platform bridges via events

Listen to `BeforeBuildPlatformBridgeEvent` to modify options before bridge instantiation:

```php
#[AsEventListener('my-ext/custom-bridge-options')]
public function __invoke(BeforeBuildPlatformBridgeEvent $event): void
{
    $options = $event->getOptions();
    $options['modelCatalog']['additionalModels'] = [...];
    $event->setOptions($options);
}
```

See `EventListener\PlatformBridgeBuilder` for a real example (LM Studio dynamic model discovery).

### AI namespace

The `AI\` namespace groups all AI-specific infrastructure. Current sub-namespaces:

- `AI\Assistant\` — Assistant handler implementations (`AssistantInterface`, `A11yAssistant`, `InlineChatAssistant`)
- `AI\Platform\` — Platform bridge layer (reflection, connection, resolution, model catalog filtering)

Future sub-namespaces may include `AI\MCP\`, `AI\Tool\`, etc.

## Site configuration format

On disk (`config.yaml`):

```yaml
assist:
  default: true
  platforms:
    - enabled: true
      name: 'My Platform'
      package: symfony/ai-open-ai-platform
      options:
        baseUrl: 'https://api.openai.com/'
      authorization:
        type: bearer          # bearer | api-key | none
        token: sk-...
      models:
        - gpt-4o
  assistants:
    typo3-assist-a11y:
      model: gpt-4o@symfony/ai-open-ai-platform
```

After `SiteConfigurationAssistNormalizer` flattens for TCA, the runtime keys are `assistDefault`, `assistPlatforms[]` (with flat `baseUrl`, `authorizationType`, `authorizationToken`), and `assistAssistants[]`.

## Conventions

- **Immutability**: all domain objects and services are `final readonly`
- **Enums**: string-backed enums in `Domain\Enum\` for `Availability`, `AssistantMode`, `AssistantCapability`, `ProgressItemType`
- **Factory methods**: `createFromConfiguration()` on `Assistant` and `AssistantTrigger` — validates input, throws `\InvalidArgumentException` with numeric codes
- **`@internal`**: `AI\Platform\PlatformBridge`, `AI\Platform\PlatformConnector`, `AI\Platform\PlatformReflector`, `AI\Platform\FilteredModelCatalog`, `Domain\Model\Assistant`, `Domain\Model\AssistantTrigger`, `BeforeBuildPlatformBridgeEvent`, all controllers
- **Strict types**: all files declare `strict_types=1`
- **Named constructor parameters**: domain objects use named params for clarity
- **Attributes**: `#[AsController]`, `#[AsEventListener('identifier')]`, `#[Autoconfigure(public: true)]`
- **Exception codes**: all exceptions use numeric timestamp-based codes (e.g. `1771009690`)

## Testing

Functional tests extend `FunctionalTestCase` and use `AssistBasedTestTrait`:

```php
class MyTest extends FunctionalTestCase
{
    use AssistBasedTestTrait;

    protected array $coreExtensionsToLoad = ['assist'];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->buildAssistSiteConfiguration(
            'MySiteId', 1, '/',
            [self::ASSIST_PLATFORM_NUMB_ENCORE]   // enables test platform
        );
    }
}
```

- Test methods use `#[Test]` attribute
- The local test package `typo3/symfony-ai-numb-platform` lives in `packages/symfony-ai-numb-platform/` and provides a deterministic `numb-encore` model
- Use `$this->get(ServiceClass::class)` for DI in tests

## Common tasks

### Adding a new assistant

1. Create handler class implementing `AssistantInterface` in `Classes/AI/Assistant/`
2. Register in `Configuration/Backend/Assistants.php` with mode, capabilities, handler, trigger
3. Flush caches (assistant configs are cached in `cache.core`)

### Adding a new platform

1. Require the Symfony AI platform package in `composer.json`
2. The package must expose a `PlatformFactory` and `ModelCatalog` under its PSR-4 root namespace
3. Users configure the platform in site YAML under `assist.platforms[]`
4. If the platform needs special bridge behavior, add an event listener for `BeforeBuildPlatformBridgeEvent`

### Working with capabilities

- `AssistantCapability` enum maps high-level TYPO3 concepts to one or more Symfony AI `Capability` values
- `messages` → `INPUT_MESSAGES` + `OUTPUT_TEXT`
- `toolCalling` → `TOOL_CALLING`
- `inputImage` → `INPUT_IMAGE`
- Use `AssistantCapability::normalize()` to convert any capability format to `Capability[]`
- Model matching in `PlatformAjaxController` checks that a model satisfies all required capabilities
