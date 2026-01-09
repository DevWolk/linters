# Configuration Guide

Complete guide for configuring `devwolk/linters` in your project.

## Table of Contents

- [Overview](#overview)
- [Configuration Structure](#configuration-structure)
- [Rector Configuration](#rector-configuration)
- [PHP-CS-Fixer Configuration](#php-cs-fixer-configuration)
- [PHPStan Configuration](#phpstan-configuration)
- [PHP_CodeSniffer Configuration](#php_codesniffer-configuration)
- [PHPMD Configuration](#phpmd-configuration)
- [Advanced Configuration](#advanced-configuration)

## Overview

All tools are configured through the `extra.linters` section in your project's `composer.json`. This centralized approach ensures consistency and simplifies maintenance.

## Configuration Structure

The basic structure for `extra.linters`:

```json
{
  "extra": {
    "linters": {
      "tool-name": {
        "paths": ["/path/to/analyze"],
        "skip": ["/path/to/exclude"],
        "additional-settings": "value"
      }
    }
  }
}
```

## Rector Configuration

### Basic Configuration

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/app", "/src", "/tests"],
        "skip": ["/app/legacy", "/vendor"]
      }
    }
  }
}
```

### Available Options

- **paths** (array): Directories to process
- **skip** (array): Paths to exclude from processing
- **frameworks** (array|string|object): Enable framework presets (`laravel`, `symfony`)
- **cache_dir** (string): Optional cache directory for Rector

### Laravel Projects

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/app", "/config", "/database", "/tests"],
        "skip": [
          "/app/Http/Middleware/TrustProxies.php",
          "/bootstrap",
          "/storage"
        ]
      }
    }
  }
}
```

### Symfony Projects

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/src", "/tests"],
        "skip": ["/src/Kernel.php", "/var", "/vendor"]
      }
    }
  }
}
```

## PHP-CS-Fixer Configuration

### Basic Configuration

```json
{
  "extra": {
    "linters": {
      "php-cs-fixer": {
        "paths": ["/app", "/src"],
        "skip": ["*.blade.php", "*.twig"]
      }
    }
  }
}
```

### Available Options

- **paths** (array): Directories to analyze
- **skip** (array): File patterns to exclude

### Laravel Projects

```json
{
  "extra": {
    "linters": {
      "php-cs-fixer": {
        "paths": ["/app", "/config", "/database"],
        "skip": [
          "*.blade.php",
          "/app/Http/Middleware/TrustProxies.php"
        ]
      }
    }
  }
}
```

## PHPStan Configuration

### Basic Configuration

```json
{
  "extra": {
    "linters": {
      "phpstan": {
        "paths": ["/app", "/src"],
        "skip": ["/vendor", "/tests"],
        "level": 8,
        "target": "./phpstan.neon"
      }
    }
  }
}
```

### Available Options

- **paths** (array, required): Directories to analyze
- **skip** (array): Paths to exclude
- **level** (int|string): Analysis level (0-9 or 'max')
- **baseline** (string): Path to baseline file (optional)
- **config** (object): Additional configuration merged into the generated NEON
- **target** (string): Output file for `linters generate phpstan` or `linters run phpstan`
- **template** (string): Template file for `linters generate phpstan`

### Analysis Levels

| Level | Description |
|-------|-------------|
| 0 | Basic checks only |
| 1-4 | Gradually stricter rules |
| 5-7 | Production-ready strictness |
| 8 | Recommended for new projects |
| 9/max | Maximum strictness |

### Using Baseline

For existing projects with many errors:

```json
{
  "extra": {
    "linters": {
      "phpstan": {
        "paths": ["/app"],
        "level": 8,
        "baseline": "phpstan-baseline.neon",
        "target": "./phpstan.neon"
      }
    }
  }
}
```

Generate baseline:
```bash
composer phpstan-baseline
```

## PHP_CodeSniffer Configuration

### Basic Configuration

```json
{
  "extra": {
    "linters": {
      "phpcs": {
        "paths": ["/app", "/src"],
        "skip": [],
        "target": "./phpcs.xml"
      }
    }
  }
}
```

### Available Options

- **paths** (array, required): Directories to check
- **skip** (array): Patterns to exclude
- **target** (string): Output file for `linters generate phpcs` or `linters run phpcs`
- **template** (string): Template file for `linters generate phpcs`

## PHPMD Configuration

### Basic Configuration

```json
{
  "extra": {
    "linters": {
      "phpmd": {
        "paths": ["/app", "/src"],
        "skip": [],
        "target": "./phpmd.ruleset.xml",
        "format": "text"
      }
    }
  }
}
```

### Available Options

- **paths** (array, required): Directories to analyze
- **skip** (array): Paths to exclude
- **rulesets** (array): Override default PHPMD rulesets
- **format** (string): Output format passed to phpmd (text, xml, html)
- **target** (string): Output file for `linters generate phpmd` or `linters run phpmd`
- **template** (string): Template file for `linters generate phpmd`

## Advanced Configuration

### Multiple Environments

Use different configurations for development and CI:

**composer.json:**
```json
{
  "extra": {
    "linters": {
      "phpstan": {
        "paths": ["/app"],
        "level": 6,
        "target": "./phpstan.neon"
      }
    }
  }
}
```

**composer.ci.json:** (CI-specific overrides)
```json
{
  "extra": {
    "linters": {
      "phpstan": {
        "level": 8
      }
    }
  }
}
```

### Extending Base Configurations

Use `extra.linters.phpstan.config` to merge additional PHPStan settings:

```json
{
  "extra": {
    "linters": {
      "phpstan": {
        "config": {
          "parameters": {
            "ignoreErrors": [
              "#Call to deprecated#"
            ]
          }
        }
      }
    }
  }
}
```

### Per-Directory Configuration

Analyze different directories with different settings by running commands multiple times with different configs.

## Default Values

No implicit defaults are applied for `*.paths`, `*.target`, or `phpmd.format`.

## Configuration Examples

### Monorepo Setup

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": [
          "/packages/core/src",
          "/packages/api/src",
          "/packages/web/src"
        ],
        "skip": ["/packages/*/vendor"]
      }
    }
  }
}
```

### Legacy Code Migration

Start with low strictness and gradually increase:

```json
{
  "extra": {
    "linters": {
      "phpstan": {
        "paths": ["/app"],
        "level": 4,
        "baseline": "phpstan-baseline.neon",
        "target": "./phpstan.neon"
      },
      "rector": {
        "paths": ["/app/Services"],
        "skip": ["/app/Legacy"]
      }
    }
  }
}
```

## Troubleshooting

### Configuration Not Loaded

Ensure:
1. Configuration is in root `composer.json`
2. JSON syntax is valid
3. Paths start with `/` (e.g., `/src` not `src`)

### Paths Not Found

Use absolute paths from project root:
- ✅ `/app/Models`
- ❌ `app/Models`
- ❌ `./app/Models`

For more help, see [TROUBLESHOOTING.md](./TROUBLESHOOTING.md).
