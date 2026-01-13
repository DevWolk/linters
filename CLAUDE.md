# CLAUDE.md

Instructions for AI assistants working with this codebase.

## Project Overview

Composer package (`devwolk/linters`) providing centralized PHP linter configurations. Consumers configure only paths/excludes/frameworks - rules are immutable.

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
src/Utils/ConfigurationLoader.php   Reads extra.linters from composer.json → DTOs
    ↓
src/ConfigGenerator/*               Tool-specific generators
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
buildCommand() → "phpstan analyze --configuration=./phpstan.neon"
    ↓
passthru() → exit code
```

### Key Classes

| Class                 | Responsibility                                                     |
|-----------------------|--------------------------------------------------------------------|
| `ConfigurationLoader` | Reads `extra.linters` from composer.json, returns typed DTOs       |
| `ToolRunner`          | `generate()` → `buildCommand()` → `passthru()`                     |
| `Tool` (enum)         | Registry: label, binary, generatedTarget, requiresGeneration       |
| `BaseToolConfig`      | Base DTO: paths, skipDirs, skipFiles, parallel, cacheDir, baseline |
| `ParallelConfig`      | Handles bool/int/object parallel config formats                    |

### Custom Rector Rules

Located in `src/Rector/Rules/`:
- `MockObjectStaticToInstanceCallRector` - `self::any()` → `$this->any()`
- `AssertInstanceToStaticCallRector` - `$this->assert*()` → `self::assert*()`

## Configuration Matrix

| Tool               | paths | skip_dirs | skip_files | parallel | cache_dir | baseline | frameworks | named-filters |
|--------------------|:-----:|:---------:|:----------:|:--------:|:---------:|:--------:|:----------:|:-------------:|
| rector             |  REQ  |    OPT    |    OPT     |   OPT*   |    OPT    |    -     |    OPT     |       -       |
| php-cs-fixer       |  REQ  |    OPT    |    OPT     |   OPT    |    OPT    |    -     |     -      |       -       |
| phpstan            |  REQ  |    OPT    |    OPT     |   OPT    |    OPT    |   OPT    |     -      |       -       |
| phpcs              |  REQ  |    OPT    |    OPT     |   OPT    |    OPT    |    -     |     -      |       -       |
| phpmd              |  REQ  |    OPT    |    OPT     |    -     |     -     |   OPT    |     -      |       -       |
| composer-unused    |   -   |     -     |     -      |    -     |     -     |    -     |     -      |      OPT      |
| composer-normalize |   -   |     -     |     -      |    -     |     -     |    -     |     -      |       -       |

*parallel enabled by default for rector

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
├── DTO/                 BaseToolConfig, ParallelConfig, *Config per tool
├── Enum/Tool.php        Tool registry enum
├── ConfigGenerator/     PhpStan/PhpCs/PhpMd/Rector/PhpCsFixer/ComposerUnused generators
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
2. If it requires generation: create DTO in `src/DTO/`, generator in `src/ConfigGenerator/`, template in `configs/`
3. Add `buildCommand()` method in `ToolRunner`
4. Update match expressions in `ToolRunner::createGenerator()` and `buildCommand()`

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

#### Step 3: Create DTO (if tool has config options)

Create `src/DTO/NewToolConfig.php`:

```php
<?php

declare(strict_types=1);

namespace Linters\DTO;

use Linters\Utils\ConfigValidation;

final readonly class NewToolConfig extends BaseToolConfig implements ToolConfigInterface
{
    public static function fromArray(array $config): self
    {
        // Use requiredPaths() for tools that need paths
        $paths = ConfigValidation::requiredPaths($config['paths'] ?? [], 'new-tool');

        // Or return empty config for tools without required fields
        $skipDirs = ConfigValidation::optionalStringList($config['skip_dirs'] ?? null);
        $skipFiles = ConfigValidation::optionalStringList($config['skip_files'] ?? null);
        $parallel = ParallelConfig::fromMixed($config['parallel'] ?? null);
        $cacheDir = $config['cache_dir'] ?? null;
        $baseline = $config['baseline'] ?? null;

        return new self($paths, $skipDirs, $skipFiles, $parallel, $cacheDir, $baseline);
    }
}
```

#### Step 4: Add Getter to ConfigurationLoader

Edit `src/Utils/ConfigurationLoader.php`:

```php
public function getNewToolConfig(): NewToolConfig
{
    return NewToolConfig::fromArray($this->getToolConfig(Tool::NEW_TOOL->value));
}
```

#### Step 5: Create Config Generator

**For Generated configs** (XML/NEON template-based):

Create `src/ConfigGenerator/NewToolConfigGenerator.php`:

```php
<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

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

use Linters\Enum\Tool;

class NewToolConfigGenerator extends AbstractStubConfigGenerator
{
    protected function getSourceConfigPath(): string
    {
        return Tool::NEW_TOOL->sourceConfigFileName();
    }

    protected function getToolName(): string
    {
        return 'New Tool';
    }
}
```

And add source config path to `Tool::sourceConfigFileName()`.

#### Step 6: Create Template/Config File

- For Generated: `configs/new-tool-template.xml`
- For Dynamic: `configs/new-tool.php` with stub that requires package config

#### Step 7: Update ToolRunner

Edit `src/Service/ToolRunner.php`:

```php
// In createGenerator():
Tool::NEW_TOOL => new NewToolConfigGenerator($this->loader),

// In buildCommand():
Tool::NEW_TOOL => $this->buildNewToolCommand($bin, $target),

// Add new method:
private function buildNewToolCommand(string $binary, string $target): string
{
    return escapeshellarg($binary) . ' --config=' . escapeshellarg($target);
}
```

#### Step 8: Update Configuration Matrix

Update the table in this file (CLAUDE.md) with new tool's supported options.

#### Step 9: Run Checks

```bash
make fix-syntax-completely
```

### Code Style Requirements

- Use `ConfigValidation::isNonEmptyString()` for null/empty checks
- Use `ConfigValidation::requiredPaths()` for required path arrays
- Use `Path::join()` for path construction (not rtrim + concatenation)
- All DTO classes must be `final readonly`
- All config classes must implement `ToolConfigInterface`
