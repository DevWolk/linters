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

1. Add case to `src/Enum/Tool.php` (label, binary, generatedTarget, requiresGeneration)
2. If it requires generation: create DTO in `src/DTO/`, generator in `src/ConfigGenerator/`, template in `configs/`
3. Add `buildCommand()` method in `ToolRunner`
4. Update match expressions in `ToolRunner::createGenerator()` and `buildCommand()`
