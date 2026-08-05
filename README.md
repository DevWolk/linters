# Linters Library

Centralized PHP linter configurations. Rules are bundled - you configure only paths.

## Installation

```bash
composer require --dev devwolk/linters
```

**Requirements:** PHP 8.4.1+

## Supported Tools

| Tool                                                                  | Description                      |
|-----------------------------------------------------------------------|----------------------------------|
| [Rector](https://getrector.com/documentation)                         | Auto-refactoring, PHP migrations |
| [PHP-CS-Fixer](https://cs.symfony.com/doc/usage.html)                 | Code formatting (PSR-12)         |
| [PHPStan](https://phpstan.org/user-guide/getting-started)             | Static analysis (level 8)        |
| [PHPCS](https://github.com/PHPCSStandards/PHP_CodeSniffer)            | Code style (PSR-12 + Slevomat)   |
| [PHPCBF](https://github.com/PHPCSStandards/PHP_CodeSniffer)           | Auto-fix PHPCS violations        |
| [PHPMD](https://phpmd.org/documentation/index.html)                   | Code quality detector            |
| [composer-unused](https://github.com/composer-unused/composer-unused) | Unused dependencies              |
| [composer-normalize](https://github.com/ergebnis/composer-normalize)  | Normalize composer.json          |

## Configuration

An example of the full `composer.json` configuration:

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["app", "database", "tests", "routes"],
        "php-version": "8.4",
        "skip-dirs": ["app/Http/Requests"],
        "skip-files": ["app/Providers/AutoWireServiceProvider.php"],
        "sets": [
          "laravel13",
          "phpunit13",
          "doctrine"
        ],
        "parallel": {
          "enabled": true,
          "files-per-process": 40,
          "timeout": 360,
          "max-processes": 2
        },
        "import-names": {
          "import-names": true,
          "import-doc-block-names": false,
          "import-short-classes": false,
          "remove-unused-imports": true
        },
        "cache-dir": ".cache/rector",
        "memory-limit": "2048M"
      },
      "php-cs-fixer": {
        "paths": ["app", "config", "database", "routes"],
        "skip-dirs": ["bootstrap", "config", "docker", "public", "resources", "routes", "storage", "vendor"],
        "skip-files": [],
        "parallel": true,
        "cache-dir": ".cache/php-cs-fixer"
      },
      "phpcs": {
        "paths": ["app", "config", "database", "routes"],
        "skip-dirs": ["vendor"],
        "skip-files": ["tests/TestCase.php", "*\\.blade\\.php$"],
        "parallel": 4,
        "cache-dir": ".cache/phpcs",
        "rule-excludes": {
          "Generic.Metrics.CyclomaticComplexity.TooHigh": ["tests/*", "src/Utils/ConfigurationLoader.php"],
          "Squiz.WhiteSpace.ScopeClosingBrace": ["tests/*", "src/Utils/ConfigurationLoader.php"]
        }
      },
      "phpmd": {
        "paths": ["app", "tests"],
        "skip-dirs": ["vendor", "storage"],
        "parallel": 4,
        "cache-dir": ".cache/phpmd"
      }
    }
  }
}
```

### Options

| Option          | Type                | Tools                                | Description                                                  |
|-----------------|---------------------|--------------------------------------|--------------------------------------------------------------|
| `paths`         | `string[]`          | all*                                 | **Required.** Directories to analyze                         |
| `php-version`   | `string`            | rector                               | Target PHP version: `8.3`, `8.4`, `8.5` (default: `8.4`)     |
| `skip-dirs`     | `string[]`          | all*                                 | Directories to exclude                                       |
| `skip-files`    | `string[]`          | all*                                 | File patterns to exclude                                     |
| `parallel`      | `bool\|int\|object` | rector, php-cs-fixer, phpstan, phpcs, phpmd | Parallel execution                                    |
| `cache-dir`     | `string`            | rector, php-cs-fixer, phpstan, phpcs, phpmd | Cache directory                                       |
| `baseline`      | `string`            | phpstan, phpmd                       | Baseline file                                                |
| `sets`          | `string[]`          | rector                               | Rector sets: `laravel11`-`laravel13`, `phpunit11`-`phpunit13`, `symfony`, `doctrine` |
| `memory-limit`  | `string`            | rector                               | Memory limit (e.g., `2048M`, `4G`)                           |
| `import-names`  | `object`            | rector                               | Import names configuration (see below)                       |
| `rule-excludes` | `object`            | phpcs                                | Rule-specific exclude patterns (see below)                   |
| `named-filters` | `string[]`          | composer-unused                      | Packages to ignore                                           |

*except composer-unused and composer-normalize

### Rector Import Names

The `import-names` option controls how Rector handles `use` import statements:

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["src"],
        "import-names": {
          "import-names": true,
          "import-doc-block-names": false,
          "import-short-classes": false,
          "remove-unused-imports": true
        }
      }
    }
  }
}
```

| Key                      | Type   | Default | Description                                       |
|--------------------------|--------|---------|---------------------------------------------------|
| `import-names`           | `bool` | `true`  | Add `use` imports for fully qualified class names |
| `import-doc-block-names` | `bool` | `false` | Add `use` imports for docblock type hints         |
| `import-short-classes`   | `bool` | `false` | Import short class names (e.g., `DateTime`)       |
| `remove-unused-imports`  | `bool` | `true`  | Remove unused `use` imports                       |

All fields are optional. Omitted fields use their default values. If `import-names` is not specified at all, all defaults apply.

### PHPCS Rule Excludes

The `rule-excludes` option allows you to exclude specific PHPCS rules for certain files or directories:

```json
{
  "extra": {
    "linters": {
      "phpcs": {
        "paths": ["src", "tests"],
        "rule-excludes": {
          "Generic.Metrics.CyclomaticComplexity.TooHigh": ["tests/*", "src/Utils/ConfigurationLoader.php"],
          "Squiz.WhiteSpace.ScopeClosingBrace": ["tests/*", "src/Utils/ConfigurationLoader.php"]
        }
      }
    }
  }
}
```

This generates XML rules like:

```xml
<rule ref="Generic.Metrics.CyclomaticComplexity.TooHigh">
    <exclude-pattern>tests/*</exclude-pattern>
    <exclude-pattern>src/Utils/ConfigurationLoader.php</exclude-pattern>
</rule>
```

To exclude a rule entirely, use `*` as the pattern:

```json
{
  "rule-excludes": {
    "Squiz.WhiteSpace.ScopeClosingBrace": ["*"]
  }
}
```

## Usage

```bash
./vendor/bin/linters run rector
./vendor/bin/linters run php-cs-fixer
./vendor/bin/linters run phpstan
./vendor/bin/linters run phpcs
./vendor/bin/linters run phpcbf
./vendor/bin/linters run phpmd
./vendor/bin/linters run composer-unused
./vendor/bin/linters run composer-normalize
```

Extra arguments can be passed after `--`:

```bash
./vendor/bin/linters run rector -- --dry-run
./vendor/bin/linters run php-cs-fixer -- --dry-run --diff
./vendor/bin/linters run phpstan -- --generate-baseline
```

## Architecture

```
src/
├── Console/Command/     CLI commands (run, generate)
├── Service/             ToolRunner orchestration
├── CommandBuilder/      Command builders (one per tool)
├── ConfigGenerator/     Config file generators
├── DTO/                 Configuration DTOs
├── Enum/                Tool registry
└── Utils/               ConfigurationLoader, validation
```

Each tool has:
- **ConfigGenerator** — generates config files (phpstan.neon, phpcs.xml, etc.)
- **CommandBuilder** — builds CLI command with proper arguments

## Rector for Laravel

Requires: `composer require --dev driftingly/rector-laravel:^2.5`

## Baseline

For legacy projects with many existing issues, generate a baseline to ignore them:

**PHPStan:**
```bash
./vendor/bin/linters run phpstan -- --generate-baseline
```
Then add to config: `"baseline": "phpstan-baseline.neon"`

**PHPMD:**
```bash
./vendor/bin/linters run phpmd -- --generate-baseline
```
Then add to config: `"baseline": "phpmd.baseline.xml"`

## Examples

See `examples/` for Laravel and Symfony configurations.
