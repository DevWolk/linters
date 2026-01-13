# devwolk/linters

Centralized PHP linter configurations. Rules are bundled - you configure only paths.

## Installation

```bash
composer require --dev devwolk/linters
```

**Requirements:** PHP 8.2+

## Supported Tools

| Tool               | Description                      |
|--------------------|----------------------------------|
| Rector             | Auto-refactoring, PHP migrations |
| PHP-CS-Fixer       | Code formatting (PSR-12)         |
| PHPStan            | Static analysis (level 8)        |
| PHPCS              | Code style (PSR-12 + Slevomat)   |
| PHPMD              | Code quality detector            |
| composer-unused    | Unused dependencies              |
| composer-normalize | Normalize composer.json          |

## Configuration

Add to `composer.json`:

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["src", "tests"],
        "skip_dirs": ["vendor"],
        "frameworks": ["laravel"],
        "parallel": true,
        "cache_dir": ".cache/rector"
      },
      "phpstan": {
        "paths": ["src"],
        "baseline": "phpstan-baseline.neon"
      },
      "phpcs": {
        "paths": ["src"],
        "parallel": 4
      }
    }
  }
}
```

### Options

| Option          | Type                | Tools                                | Description                          |
|-----------------|---------------------|--------------------------------------|--------------------------------------|
| `paths`         | `string[]`          | all*                                 | **Required.** Directories to analyze |
| `skip_dirs`     | `string[]`          | all*                                 | Directories to exclude               |
| `skip_files`    | `string[]`          | all*                                 | File patterns to exclude             |
| `parallel`      | `bool\|int\|object` | rector, php-cs-fixer, phpstan, phpcs | Parallel execution                   |
| `cache_dir`     | `string`            | rector, php-cs-fixer, phpstan, phpcs | Cache directory                      |
| `baseline`      | `string`            | phpstan, phpmd                       | Baseline file                        |
| `frameworks`    | `string[]`          | rector                               | Framework: `laravel`                 |
| `named-filters` | `string[]`          | composer-unused                      | Packages to ignore                   |

*except composer-unused and composer-normalize

## Usage

```bash
./vendor/bin/linters run rector
./vendor/bin/linters run php-cs-fixer
./vendor/bin/linters run phpstan
./vendor/bin/linters run phpcs
./vendor/bin/linters run phpmd
./vendor/bin/linters run composer-unused
./vendor/bin/linters run composer-normalize
```

## Laravel

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["app", "config", "database", "routes", "tests"],
        "skip_dirs": ["bootstrap", "storage"],
        "frameworks": ["laravel"]
      }
    }
  }
}
```

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
