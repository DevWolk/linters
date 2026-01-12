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
- [Composer-unused Configuration](#composer-unused-configuration)
- [Advanced Configuration](#advanced-configuration)

## Overview

All tools are configured through the `extra.linters` section in your project's `composer.json`. This centralized approach ensures consistency and simplifies maintenance.
Paths and patterns are used as-is (relative or absolute).

## Configuration Structure

The basic structure for `extra.linters`:

```json
{
  "extra": {
    "linters": {
      "tool-name": {
        "paths": ["src"],
        "skip_dirs": ["vendor"],
        "skip_files": ["*.generated.php"]
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
        "paths": ["app", "src", "tests"],
        "skip_dirs": ["app/legacy", "vendor"],
        "skip_files": [],
        "cache_dir": ".cache/rector"
      }
    }
  }
}
```

### Available Options

- **paths** (array): Directories/files to process
- **skip_dirs** (array): Directories to exclude from processing
- **skip_files** (array): File globs or path patterns to exclude
- **frameworks** (array|string): Enable framework presets (for example: `laravel`)
- **parallel** (bool|int|object): Enable parallel mode (`true`, `4`, or object with `enabled`, `timeout`, `max_processes`, `files_per_process`)
- **cache_dir** (string): Optional cache directory for Rector

### Framework Presets

Use `rector.frameworks` to load framework-specific rule sets as a string or array:

**String**
```json
{
  "extra": {
    "linters": {
      "rector": {
        "frameworks": "laravel"
      }
    }
  }
}
```

**Array**
```json
{
  "extra": {
    "linters": {
      "rector": {
        "frameworks": ["laravel", "symfony"]
      }
    }
  }
}
```

### Laravel Projects

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["app", "config", "database", "tests"],
        "skip_dirs": ["bootstrap", "storage"],
        "skip_files": ["app/Http/Middleware/TrustProxies.php"]
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
        "paths": ["src", "tests"],
        "skip_dirs": ["var", "vendor"],
        "skip_files": ["src/Kernel.php"]
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
        "paths": ["app", "src"],
        "skip_dirs": ["resources/views", "templates"],
        "skip_files": ["*.generated.php"]
      }
    }
  }
}
```

### Available Options

- **paths** (array): Directories/files to analyze
- **skip_dirs** (array): Directories to exclude
- **skip_files** (array): File name globs or path patterns to exclude
- **parallel** (bool|int|object): Enable parallel mode (`true`, `4`, or object with `enabled`, `timeout`, `max_processes`, `files_per_process`)
- **cache_dir** (string): Optional cache directory

Note: the bundled PHP-CS-Fixer config already ignores `*.blade.php` by filename.
If a `skip_files` entry contains `/`, it is treated as a path pattern relative to the project root.

### Laravel Projects

```json
{
  "extra": {
    "linters": {
      "php-cs-fixer": {
        "paths": ["app", "config", "database"],
        "skip_dirs": ["resources/views", "storage"],
        "skip_files": ["*.generated.php"]
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
        "paths": ["app", "src"],
        "skip_dirs": ["vendor", "tests"],
        "skip_files": [],
        "baseline": "phpstan-baseline.neon",
        "cache_dir": ".cache/phpstan",
        "parallel": true
      }
    }
  }
}
```

### Available Options

- **paths** (array, required): Directories/files to analyze
- **skip_dirs** (array): Directories to exclude
- **skip_files** (array): File globs or path patterns to exclude
- **baseline** (string): Path to baseline file (optional)
- **cache_dir** (string): Optional cache directory
- **parallel** (bool|int|object): Enable parallel mode (`true`, `4`, or object with `enabled`, `timeout`, `max_processes`, `files_per_process`)

### Using Baseline

For existing projects with many errors:

```json
{
  "extra": {
    "linters": {
      "phpstan": {
        "paths": ["app"],
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

## PHP_CodeSniffer Configuration

### Basic Configuration

```json
{
  "extra": {
    "linters": {
      "phpcs": {
        "paths": ["app", "src"],
        "skip_dirs": [],
        "skip_files": [],
        "cache_dir": ".cache/phpcs",
        "parallel": 4
      }
    }
  }
}
```

### Available Options

- **paths** (array, required): Directories/files to check
- **skip_dirs** (array): Directories to exclude
- **skip_files** (array): File globs or path patterns to exclude
- **cache_dir** (string): Optional cache directory
- **parallel** (bool|int|object): Enable parallel mode (`true`, `4`, or object with `enabled`, `timeout`, `max_processes`, `files_per_process`)

## PHPMD Configuration

### Basic Configuration

```json
{
  "extra": {
    "linters": {
      "phpmd": {
        "paths": ["app", "src"],
        "skip_dirs": [],
        "skip_files": [],
        "baseline": "phpmd-baseline.xml"
      }
    }
  }
}
```

### Available Options

- **paths** (array, required): Directories/files to analyze
- **skip_dirs** (array): Directories to exclude
- **skip_files** (array): File globs or path patterns to exclude
- **baseline** (string): Path to baseline file (optional)

## Composer-unused Configuration

### Basic Configuration

```json
{
  "extra": {
    "linters": {
      "composer-unused": {
        "named-filters": [
          "wikimedia/composer-merge-plugin"
        ]
      }
    }
  }
}
```

### Available Options

- **named-filters** (array): Filter package names to ignore in composer-unused

## Advanced Configuration

### Multiple Environments

Use different configurations for development and CI:

**composer.json:**
```json
{
  "extra": {
    "linters": {
      "phpstan": {
        "paths": ["src"],
        "baseline": "phpstan-baseline.neon"
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
        "paths": ["src", "tests"]
      }
    }
  }
}
```

### Per-Directory Configuration

Analyze different directories with different settings by running commands multiple times with different configs.

## Default Values

No implicit defaults are applied; required keys like `paths` must be provided.

## Configuration Examples

### Monorepo Setup

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": [
          "packages/core/src",
          "packages/api/src",
          "packages/web/src"
        ],
        "skip_dirs": ["packages/*/vendor"],
        "skip_files": []
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
        "paths": ["app"],
        "baseline": "phpstan-baseline.neon"
      },
      "rector": {
        "paths": ["app/Services"],
        "skip_dirs": ["app/Legacy"],
        "skip_files": []
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
3. Paths and patterns match your project layout (they are used as-is)

### Paths Not Found

Paths are not normalized. Use the exact paths or globs you want the tools to receive.

For more help, see [TROUBLESHOOTING.md](./TROUBLESHOOTING.md).
