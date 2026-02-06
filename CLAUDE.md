# CLAUDE.md

Instructions for AI assistants working with this codebase.

## Project Overview

Composer package (`devwolk/linters`) providing centralized PHP linter configurations. Consumers configure only paths/excludes/sets - rules are immutable.

**7 supported tools:** Rector, PHP-CS-Fixer, PHPStan, PHPCS, PHPMD, composer-unused, composer-normalize

## Architecture

```
bin/linters                         CLI entrypoint
    ↓
src/Console/Command/
├── GenerateConfigCommand.php       `linters generate <tool>`
└── RunCommand.php                  `linters run <tool>`
    ↓
src/Service/ToolRunner.php          Orchestration: generate → build command → passthru
    ↓
├── src/ConfigGenerator/*           Tool-specific config generators
└── src/CommandBuilder/*            Tool-specific command builders
    ↓
src/Utils/ConfigurationLoader.php   Reads extra.linters from composer.json → DTOs
    ↓
configs/*                           Templates and dynamic configs
```

### Three Config Categories

| Category      | Tools                                 | How it works                                               |
|---------------|---------------------------------------|------------------------------------------------------------|
| **Generated** | phpstan, phpcs, phpmd                 | ConfigGenerator creates file in project root from template |
| **Dynamic**   | rector, php-cs-fixer, composer-unused | Stub file requires package config; DTO read at runtime     |
| **Run-only**  | composer-normalize                    | `Tool::requiresGeneration()` = false; direct execution     |

### Execution Flow

```
./vendor/bin/linters run phpstan
    ↓
RunCommand → Tool::from() → ToolRunner::run()
    ↓
requiresGeneration()? → yes → generate() → PhpStanConfigGenerator
    ↓                    no  → skip
createCommandBuilder() → PhpStanCommandBuilder
    ↓
setConfigPath() → build() → "phpstan analyze --configuration=./phpstan.neon"
    ↓
passthru() → exit code
```

### Key Classes

| Class                                  | Responsibility                                                     |
|----------------------------------------|--------------------------------------------------------------------|
| `ConfigurationLoader`                  | Reads `extra.linters` from composer.json, returns typed DTOs       |
| `ToolRunner`                           | `generate()` → `createCommandBuilder()` → `build()` → `passthru()` |
| `Tool` (enum)                          | Registry: label, binary, generatedTarget, requiresGeneration       |
| `AbstractToolConfig`                   | Base DTO: paths, skipDirs, skipFiles, parallel, cacheDir, baseline |
| `ParallelConfigOptions`                | Handles bool/int/object parallel config formats                    |
| `CommandBuilderInterface`              | Base interface for command builders                                |
| `ConfigurableCommandBuilderInterface`  | Interface for builders requiring config file                       |
| `AbstractCommandBuilder`               | Base class with `resolveBinary()`, `buildExtraArgs()`              |
| `AbstractConfigurableCommandBuilder`   | Base class with `setConfigPath()`, `getConfigPath()`               |

### CommandBuilder Pattern

Each tool has its own command builder that knows how to construct the CLI command:

```
src/CommandBuilder/
├── CommandBuilderInterface.php              # build(array $extraArgs): string
├── ConfigurableCommandBuilderInterface.php  # + setConfigPath(string $configPath): void
├── AbstractCommandBuilder.php               # Base: resolveBinary(), buildExtraArgs()
├── AbstractConfigurableCommandBuilder.php   # + setConfigPath(), getConfigPath()
├── PhpStanCommandBuilder.php                # analyze --configuration=
├── PhpCsCommandBuilder.php                  # --standard= --parallel= (phpcs + phpcbf)
├── PhpMdCommandBuilder.php                  # paths format ruleset --baseline-file=
├── RectorCommandBuilder.php                 # process --config= --clear-cache
├── PhpCsFixerCommandBuilder.php             # fix --config= --allow-risky=yes
├── ComposerUnusedCommandBuilder.php         # --configuration=
└── ComposerNormalizeCommandBuilder.php      # composer normalize (run-only, no config)
```

**Key design decisions:**
- `CommandBuilderInterface` has no `$configPath` parameter — for run-only tools
- `ConfigurableCommandBuilderInterface` adds `setConfigPath()` — for tools requiring config
- Each builder resolves its own binary via `$this->resolveBinary()`
- `PhpCsCommandBuilder` handles both phpcs and phpcbf (same config, same parallel logic)

### Custom Rector Rules

Located in `src/Rector/Rules/`:
- `MockObjectStaticToInstanceCallRector` - `self::any()` → `$this->any()`
- `AssertInstanceToStaticCallRector` - `$this->assert*()` → `self::assert*()`

## Configuration Matrix

| Tool               | paths | php-version | skip-dirs | skip-files | parallel | cache-dir | baseline | sets | memory-limit | named-filters | rule-excludes |
|--------------------|:-----:|:-----------:|:---------:|:----------:|:--------:|:---------:|:--------:|:----:|:------------:|:-------------:|:-------------:|
| rector             |  REQ  |    OPT**    |    OPT    |    OPT     |   OPT*   |    OPT    |    -     | OPT  |     OPT      |       -       |       -       |
| php-cs-fixer       |  REQ  |      -      |    OPT    |    OPT     |   OPT    |    OPT    |    -     |  -   |      -       |       -       |       -       |
| phpstan            |  REQ  |      -      |    OPT    |    OPT     |   OPT    |    OPT    |   OPT    |  -   |      -       |       -       |       -       |
| phpcs              |  REQ  |      -      |    OPT    |    OPT     |   OPT    |    OPT    |    -     |  -   |      -       |       -       |      OPT      |
| phpmd              |  REQ  |      -      |    OPT    |    OPT     |    -     |     -     |   OPT    |  -   |      -       |       -       |       -       |
| composer-unused    |   -   |      -      |     -     |     -      |    -     |     -     |    -     |  -   |      -       |      OPT      |       -       |
| composer-normalize |   -   |      -      |     -     |     -      |    -     |     -     |    -     |  -   |      -       |       -       |       -       |

*parallel enabled by default for rector
**php-version defaults to 8.4 if not specified

## Development Commands

```bash
# Full check + fix
make fix-syntax-completely

# Individual tools
make rector              # Auto-fix with Rector
make rector-dry-run      # Dry run
make php-cs-fixer        # Auto-fix with PHP-CS-Fixer
make phpstan             # Static analysis
make test-unit           # PHPUnit tests
```

## File Structure

```
src/
├── Console/Command/     GenerateConfigCommand, RunCommand
├── Service/             ToolRunner
├── CommandBuilder/      Command builders (one per tool)
├── ConfigGenerator/     Config generators (phpstan, phpcs, phpmd, rector, php-cs-fixer, composer-unused)
├── DTO/                 AbstractToolConfig, *Config per tool
├── Enum/Tool.php        Tool registry enum
├── Utils/               ConfigurationLoader, ConfigValidation
└── Rector/
    ├── Rules/           Custom Rector rules
    └── Configs/Sets/    app-rules.php, doctrine.php, laravel.php

configs/                 Templates (phpstan.neon, phpcs.xml, phpmd.ruleset.xml)
                         Dynamic configs (rector.php, .php-cs-fixer.dist.php, composer-unused.php)
```

## Adding a New Tool

### Quick Checklist

1. Add case to `src/Enum/Tool.php` (label, binary, generatedTarget, requiresGeneration)
2. Create CommandBuilder in `src/CommandBuilder/`
3. If it requires generation: create DTO in `src/DTO/`, generator in `src/ConfigGenerator/`, template in `configs/`
4. Update match expressions in `ToolRunner::createGenerator()` and `createCommandBuilder()`

### Detailed Instructions for Claude Code CLI

When user asks to add a new linter tool, follow these steps:

#### Step 1: Determine Tool Category

Ask user or determine from tool documentation:
- **Generated**: Tool reads config from file (XML, NEON, etc.) → need template + generator
- **Dynamic**: Tool uses PHP config that can require our package → need stub config
- **Run-only**: Tool needs no config → only command builder

#### Step 2: Add Tool Enum Case

Edit `src/Enum/Tool.php`:

```php
case NEW_TOOL = 'new-tool';  // CLI argument name

// Update all match expressions:
public function label(): string {
    return match ($this) {
        // ...
        self::NEW_TOOL => 'New Tool',
    };
}

public function generatedTarget(): ?string {
    return match ($this) {
        // ...
        self::NEW_TOOL => 'new-tool.xml',  // or null if run-only
    };
}

public function requiresGeneration(): bool {
    return match ($this) {
        // ...
        self::NEW_TOOL => true,  // false for run-only
    };
}
```

#### Step 3: Create Command Builder

**For tools requiring config** (most tools):

Create `src/CommandBuilder/NewToolCommandBuilder.php`:

```php
<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

use Linters\CommandBuilder\Contracts\AbstractConfigurableCommandBuilder;

final class NewToolCommandBuilder extends AbstractConfigurableCommandBuilder
{
    public function build(array $extraArgs): string
    {
        $command = $this->escapeArg($this->resolveBinary())
            . ' --config=' . $this->escapeArg($this->getConfigPath());

        return $command . $this->buildExtraArgs($extraArgs);
    }
}
```

**For run-only tools** (no config file):

```php
<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

use Linters\CommandBuilder\Contracts\AbstractCommandBuilder;

final class NewToolCommandBuilder extends AbstractCommandBuilder
{
    public function build(array $extraArgs): string
    {
        return 'new-tool-command' . $this->buildExtraArgs($extraArgs);
    }
}
```

#### Step 4: Create DTO (if tool has config options)

Create `src/DTO/NewToolConfig.php`:

```php
<?php

declare(strict_types=1);

namespace Linters\DTO;

use Linters\DTO\Contracts\AbstractToolConfig;
use Linters\DTO\Contracts\ToolConfigInterface;
use Linters\Utils\ConfigValidation;
use Linters\Utils\ParallelConfigOptions;

final readonly class NewToolConfig extends AbstractToolConfig implements ToolConfigInterface
{
    public static function fromArray(array $config): self
    {
        $paths = ConfigValidation::requiredPaths($config['paths'] ?? [], 'new-tool');
        $skipDirs = ConfigValidation::optionalStringList($config['skip-dirs'] ?? null);
        $skipFiles = ConfigValidation::optionalStringList($config['skip-files'] ?? null);
        $parallel = ParallelConfigOptions::fromMixed($config['parallel'] ?? null);
        $cacheDir = $config['cache-dir'] ?? null;
        $baseline = $config['baseline'] ?? null;

        return new self($paths, $skipDirs, $skipFiles, $parallel, $cacheDir, $baseline);
    }
}
```

#### Step 5: Add Getter to ConfigurationLoader

Edit `src/Utils/ConfigurationLoader.php`:

```php
public function getNewToolConfig(): NewToolConfig
{
    return NewToolConfig::fromArray($this->getToolConfig(Tool::NEW_TOOL->value));
}
```

#### Step 6: Create Config Generator

**For Generated configs** (XML/NEON template-based):

Create `src/ConfigGenerator/NewToolConfigGenerator.php`:

```php
<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

use Linters\ConfigGenerator\Contracts\ConfigGeneratorInterface;
use Linters\Utils\ConfigurationLoader;

class NewToolConfigGenerator implements ConfigGeneratorInterface
{
    private const string PACKAGE_CONFIG_PATH = __DIR__ . '/../../configs/new-tool-template.xml';

    public function __construct(protected ConfigurationLoader $loader = new ConfigurationLoader())
    {
    }

    public function generate(string $targetPath): void
    {
        $config = $this->loader->getNewToolConfig();
        // Build config content...
        file_put_contents($targetPath, $content);
    }
}
```

**For Dynamic configs** (PHP stub):

Create `src/ConfigGenerator/NewToolConfigGenerator.php` extending `AbstractStubConfigGenerator`:

```php
<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

use Linters\ConfigGenerator\Contracts\AbstractStubConfigGenerator;
use Linters\Enum\Tool;

class NewToolConfigGenerator extends AbstractStubConfigGenerator
{
    public function __construct(ConfigurationLoader $loader)
    {
        parent::__construct(Tool::NEW_TOOL, $loader);
    }
}
```

And add source config path to `Tool::sourceConfigFileName()`.

#### Step 7: Create Template/Config File

- For Generated: `configs/new-tool-template.xml`
- For Dynamic: `configs/new-tool.php` with stub that requires package config

#### Step 8: Update ToolRunner

Edit `src/Service/ToolRunner.php`:

```php
// In createGenerator():
Tool::NEW_TOOL => new NewToolConfigGenerator($this->loader),

// In createCommandBuilder():
Tool::NEW_TOOL => new NewToolCommandBuilder($tool, $this->loader),
```

#### Step 9: Update Configuration Matrix

Update the table in this file (CLAUDE.md) with new tool's supported options.

#### Step 10: Run Checks

```bash
make fix-syntax-completely
```

### Code Style Requirements

- Use `ConfigValidation::isNonEmptyString()` for null/empty checks
- Use `ConfigValidation::requiredPaths()` for required path arrays
- Use `Path::join()` for path construction (not rtrim + concatenation)
- All DTO classes must be `final readonly`
- All config classes must implement `ToolConfigInterface`
- CommandBuilders extending `AbstractConfigurableCommandBuilder` must NOT be `readonly`
- CommandBuilders extending `AbstractCommandBuilder` (run-only) can be `final class`

### Design Notes

**Testing strategy:**
- Unit tests cover ConfigurationLoader, ConfigGenerators (phpstan, phpcs, phpmd)
- Integration tests cover ToolRunner with mock binaries
- No tests for Rector rules — real usage is the test
- No tests for simple DTOs like `ComposerUnusedConfig` — trivial code

**Inheritance in DTOs:**
- `PhpMdConfig` inherits `parallel` and `cacheDir` from `AbstractToolConfig` but doesn't use them — acceptable trade-off for unified API
- `ComposerUnusedConfig` doesn't extend `AbstractToolConfig` — has completely different structure (only `namedFilters`)

**ConfigValidation::normalizeSets():**
- Silently ignores unknown set names — acceptable for internal library, experienced devs will notice typos
