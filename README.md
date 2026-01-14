# Linters Library

Centralized PHP linter configurations. Rules are bundled - you configure only paths.

## Installation

```bash
composer require --dev devwolk/linters
```

**Requirements:** PHP 8.4+

## Supported Tools

| Tool                                                                  | Description                      |
|-----------------------------------------------------------------------|----------------------------------|
| [Rector](https://getrector.com/documentation)                         | Auto-refactoring, PHP migrations |
| [PHP-CS-Fixer](https://cs.symfony.com/doc/usage.html)                 | Code formatting (PSR-12)         |
| [PHPStan](https://phpstan.org/user-guide/getting-started)             | Static analysis (level 8)        |
| [PHPCS](https://github.com/squizlabs/PHP_CodeSniffer)                 | Code style (PSR-12 + Slevomat)   |
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
        "skip_dirs": ["app/Http/Requests"],
        "skip_files": ["app/Providers/AutoWireServiceProvider.php"],
        "sets": [
          "laravel12",
          "phpunit12",
          "doctrine"
        ],
        "parallel": {
          "parallel": true,
          "filesPerProcess": 40,
          "timeout":360,
          "maxProcesses": 2
        },
        "cache_dir": ".cache/rector"
      },
      "php-cs-fixer": {
        "paths": ["app", "config", "database", "routes"],
        "skip_dirs": ["bootstrap", "config", "docker", "public", "resources", "routes", "storage", "vendor"],
        "skip_files": [],
        "parallel": true,
        "cache_dir": ".cache/php-cs-fixer"
      },
      "phpcs": {
        "paths": ["app", "config", "database", "routes"],
        "skip_dirs": ["vendor"],
        "skip_files": ["tests/TestCase.php", "*\\.blade\\.php$"],
        "parallel": 4,
        "cache_dir": ".cache/phpcs",
        "rule_excludes": {
          "Generic.Metrics.CyclomaticComplexity.TooHigh": ["tests/*", "src/Utils/ConfigurationLoader.php"],
          "Squiz.WhiteSpace.ScopeClosingBrace": ["tests/*", "src/Utils/ConfigurationLoader.php"]
        }
      }
    }
  }
}
```

### Options

| Option          | Type                | Tools                                | Description                                                  |
|-----------------|---------------------|--------------------------------------|--------------------------------------------------------------|
| `paths`         | `string[]`          | all*                                 | **Required.** Directories to analyze                         |
| `skip_dirs`     | `string[]`          | all*                                 | Directories to exclude                                       |
| `skip_files`    | `string[]`          | all*                                 | File patterns to exclude                                     |
| `parallel`      | `bool\|int\|object` | rector, php-cs-fixer, phpstan, phpcs | Parallel execution                                           |
| `cache_dir`     | `string`            | rector, php-cs-fixer, phpstan, phpcs | Cache directory                                              |
| `baseline`      | `string`            | phpstan, phpmd                       | Baseline file                                                |
| `sets`          | `string[]`          | rector                               | Rector sets: `laravel11`, `laravel12`, `symfony`, `doctrine` |
| `rule_excludes` | `object`            | phpcs                                | Rule-specific exclude patterns (see below)                   |
| `named-filters` | `string[]`          | composer-unused                      | Packages to ignore                                           |

*except composer-unused and composer-normalize

### PHPCS Rule Excludes

The `rule_excludes` option allows you to exclude specific PHPCS rules for certain files or directories:

```json
{
  "extra": {
    "linters": {
      "phpcs": {
        "paths": ["src", "tests"],
        "rule_excludes": {
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
  "rule_excludes": {
    "Squiz.WhiteSpace.ScopeClosingBrace": ["*"]
  }
}
```

## Usage

```bash
./vendor/bin/linters run rector
./vendor/bin/linters run php-cs-fixer
./vendor/bin/linters run phpcs
./vendor/bin/linters run phpstan
./vendor/bin/linters run phpmd
./vendor/bin/linters run composer-unused
./vendor/bin/linters run composer-normalize
```

## Rector for Laravel

Requires: `composer require --dev driftingly/rector-laravel`

## Baseline

For legacy projects:

```bash
./vendor/bin/linters generate phpstan
./vendor/bin/phpstan analyze --configuration=./phpstan.neon --generate-baseline
```

Then add `"baseline": "phpstan-baseline.neon"` to config.

## Examples

See `examples/` for Laravel and Symfony configurations.
