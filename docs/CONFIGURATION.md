# Configuration Guide

Complete guide for configuring `devwolk/linters` in your project.

## Table of Contents

- [Overview](#overview)
- [Configuration Structure](#configuration-structure)
- [Rector Configuration](#rector-configuration)
- [PHP-CS-Fixer Configuration](#php-cs-fixer-configuration)
- [PHPStan Configuration](#phpstan-configuration)
- [Psalm Configuration](#psalm-configuration)
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
      "cs-fixer": {
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
      "cs-fixer": {
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
        "level": 8
      }
    }
  }
}
```

### Available Options

- **paths** (array): Directories to analyze
- **skip** (array): Paths to exclude
- **level** (int|string): Analysis level (0-9 or 'max')
- **baseline** (string): Path to baseline file (optional)

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
        "baseline": "phpstan-baseline.neon"
      }
    }
  }
}
```

Generate baseline:
```bash
composer phpstan-baseline
```

## Psalm Configuration

### Basic Configuration

```json
{
  "extra": {
    "linters": {
      "psalm": {
        "paths": ["/app", "/src"],
        "skip": ["/vendor"],
        "config": {}
      }
    }
  }
}
```

### Available Options

- **paths** (array): Directories to analyze
- **skip** (array): Paths to exclude
- **config** (object): Additional XML configuration

### Laravel Projects with Plugin

```json
{
  "extra": {
    "linters": {
      "psalm": {
        "paths": ["/app"],
        "skip": ["/vendor", "/bootstrap"],
        "config": {
          "plugins": {
            "pluginClass": [
              {"class": "Psalm\\LaravelPlugin\\Plugin"}
            ]
          }
        }
      }
    }
  }
}
```

## PHP_CodeSniffer Configuration

### Basic Configuration

```json
{
  "extra": {
    "linters": {
      "phpcs": {
        "paths": ["/app", "/src"],
        "skip": []
      }
    }
  }
}
```

### Available Options

- **paths** (array): Directories to check
- **skip** (array): Patterns to exclude

## PHPMD Configuration

### Basic Configuration

```json
{
  "extra": {
    "linters": {
      "phpmd": {
        "paths": ["/app", "/src"],
        "skip": []
      }
    }
  }
}
```

### Available Options

- **paths** (array): Directories to analyze
- **skip** (array): Paths to exclude

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
        "level": 6
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

Create project-specific config files that extend the library configs:

**phpstan.neon:**
```neon
includes:
    - ./vendor/devwolk/linters/configs/phpstan.neon

parameters:
    # Your custom overrides
    ignoreErrors:
        - '#Call to deprecated#'
```

### Per-Directory Configuration

Analyze different directories with different settings by running commands multiple times with different configs.

## Default Values

If not specified, these defaults are used:

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/src"],
        "skip": []
      },
      "cs-fixer": {
        "paths": ["/src"],
        "skip": []
      },
      "phpstan": {
        "paths": ["/src"],
        "skip": ["/vendor"],
        "level": 8
      },
      "psalm": {
        "paths": ["/src"],
        "skip": ["/vendor"],
        "config": {}
      },
      "phpcs": {
        "paths": ["/src"],
        "skip": []
      },
      "phpmd": {
        "paths": ["/src"],
        "skip": []
      }
    }
  }
}
```

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
        "baseline": "phpstan-baseline.neon"
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
