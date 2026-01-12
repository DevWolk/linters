# devwolk/linters

Centralized PHP linter configurations and static analysis tools for PHP projects.

## Target Standard (Spec)

- Single CLI entrypoint: `./vendor/bin/linters generate <tool>` and `./vendor/bin/linters run <tool>`
- Templates for every check live under `configs/`
- No project-specific paths or defaults inside the package
- All settings come from `extra.linters` in the consuming `composer.json`
- Framework flexibility via `rector.frameworks`

## Current Status (Snapshot)

- Implemented: Rector, PHP-CS-Fixer, PHPStan, PHPCS, PHPMD, composer-unused
- CLI: `linters run` supports all tools; `linters generate` is for PHPStan/PHPCS/PHPMD
- Templates: `configs/phpstan.neon`, `configs/phpcs.xml`, `configs/phpmd.ruleset.xml`,
  plus dynamic configs for Rector, PHP-CS-Fixer, and composer-unused
- Pending: run test/make targets

## Supported Tools

| Tool                                                                  | Version | Status |
|-----------------------------------------------------------------------|---------|--------|
| [Rector](https://getrector.com/)                                      | ^2.2    | Working (dynamic config) |
| [PHP-CS-Fixer](https://cs.symfony.com/)                               | ^3.89   | Working (dynamic config) |
| [PHPStan](https://phpstan.org/)                                       | ^2.1    | Working (generated config, not verified) |
| [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)       | ^4.0    | Working (generated config, not verified) |
| [PHPMD](https://phpmd.org/)                                           | ^2.15   | Working (generated config, not verified) |
| [composer-unused](https://github.com/composer-unused/composer-unused) | ^0.9    | Working |

## Configuration (Target Schema)

All tool settings live in `extra.linters`:
All paths/patterns (including `baseline` and `cache_dir`) are used as-is (no normalization).
`skip_files` is treated as path/glob patterns (no filename-only special casing).

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["src"],
        "skip_dirs": ["vendor"],
        "skip_files": [],
        "frameworks": ["laravel"],
        "cache_dir": ".cache/rector",
        "parallel": {
          "enabled": true,
          "timeout": 120,
          "max_processes": 4,
          "files_per_process": 20
        }
      },
      "php-cs-fixer": {
        "paths": ["src"],
        "skip_dirs": ["src/Legacy"],
        "skip_files": ["*.blade.php"],
        "cache_dir": ".cache/php-cs-fixer",
        "parallel": {
          "enabled": true,
          "max_processes": 4
        }
      },
      "phpstan": {
        "paths": ["src"],
        "skip_dirs": ["vendor"],
        "skip_files": [],
        "baseline": "phpstan-baseline.neon",
        "cache_dir": ".cache/phpstan",
        "parallel": {
          "enabled": true,
          "max_processes": 4
        }
      },
      "phpcs": {
        "paths": ["src"],
        "skip_dirs": [],
        "skip_files": [],
        "cache_dir": ".cache/phpcs",
        "parallel": {
          "enabled": true,
          "max_processes": 4
        }
      },
      "phpmd": {
        "paths": ["src"],
        "skip_dirs": ["vendor"],
        "skip_files": [],
        "baseline": "phpmd-baseline.xml"
      },
      "composer-unused": {
        "named-filters": [
          "wikimedia/composer-merge-plugin"
        ]
      }
    }
  }
}
```

All tool configs live under `extra.linters.<tool>` and only the keys above are accepted.
## CLI (Single Standard)

```bash
./vendor/bin/linters run rector
./vendor/bin/linters run php-cs-fixer
./vendor/bin/linters generate phpstan
./vendor/bin/linters generate phpcs
./vendor/bin/linters generate phpmd
./vendor/bin/linters run phpstan
./vendor/bin/linters run phpcs
./vendor/bin/linters run phpmd
./vendor/bin/linters run composer-unused
```

`linters run <tool>` regenerates configs for phpstan/phpcs/phpmd on each run.
Generated configs are written to `phpstan.neon`, `phpcs.xml`, and `phpmd.ruleset.xml` in the project root.
`linters generate` is only available for `phpstan`, `phpcs`, and `phpmd`.

## Framework Support

Framework-specific Rector rules are controlled by `rector.frameworks`:

```json
{
  "extra": {
    "linters": {
      "rector": {
        "frameworks": ["laravel"]
      }
    }
  }
}
```

Framework presets are limited to Rector via `rector.frameworks`.

## Templates

`configs/` provides the shared templates used by generators:

- `configs/phpstan.neon`
- `configs/phpcs.xml`
- `configs/phpmd.ruleset.xml`
- `configs/rector.php`
- `configs/.php-cs-fixer.dist.php`
- `configs/composer-unused.php`


## Карта проекта (текущая логика)
```
bin/linters                    → Entrypoint (Symfony Console)
src/Console/Application         → Регистрирует команды generate/run
src/Console/Command/GenerateConfigCommand → Генерация конфигов для phpstan/phpcs/phpmd
src/Console/Command/RunCommand  → Генерация (если нужна) + запуск любого tool

ConfigurationLoader            → Читает extra.linters из composer.json
    └── validateConfig: только ключи из Tool enum

DTO
    ├── ToolConfigInterface    → fromArray()
    ├── BaseToolConfig         → paths/skipDirs/skipFiles/parallel/cacheDir/baseline
    ├── ParallelConfig         → enabled/timeout/maxProcesses/filesPerProcess
    ├── PhpStan/PhpCs/PhpMd/PhpCsFixer/RectorConfig (+ frameworks)
    └── ComposerUnusedConfig   → namedFilters

ConfigGenerators
    ├── PhpStanConfigGenerator → phpstan.neon (includes template + baseline + paths/excludes/tmpDir)
    ├── PhpCsConfigGenerator   → phpcs.xml (file/exclude-pattern + cache)
    └── PhpMdConfigGenerator   → phpmd.ruleset.xml (exclude-pattern)

configs/ (шаблоны и "живые" конфиги)
    ├── phpstan.neon/phpcs.xml/phpmd.ruleset.xml → шаблоны
    ├── rector.php → читает DTO + sets/skip/paths/parallel/cache
    ├── .php-cs-fixer.dist.php → читает DTO + Finder/parallel/cache
    └── composer-unused.php → читает DTO + named-filters

ToolRunner                     → resolve binary + generate (если нужно) + запуск через passthru
Tool enum                       → список инструментов + маппинг generatedTarget/packageConfigPath
```

## Логика выполнения (generate/run)
- `bin/linters generate <tool>` → ToolRunner::generate → ConfigGenerator → файл в корне проекта
- `bin/linters run <tool>` → generate (если нужно) → buildCommand (DTO + параметры) → запуск
- Инструменты без генерации используют конфиги из `configs/` напрямую

## Поддерживаемые инструменты (Tool enum)
| Tool            | paths | skip_dirs | skip_files | parallel | cache_dir | baseline | frameworks            | named-filters |
|-----------------|-------|-----------|------------|----------|-----------|----------|-----------------------|---------------|
| phpstan         | REQ   | OPT       | OPT        | OPT      | OPT       | OPT      | -                     | -             |
| phpcs           | REQ   | OPT       | OPT        | OPT      | OPT       | -        | -                     | -             |
| phpmd           | REQ   | OPT       | OPT        | -        | -         | OPT      | -                     | -             |
| rector          | REQ   | OPT       | OPT        | OPT      | OPT       | -        | OPT (laravel/symfony) | -             |
| php-cs-fixer    | REQ   | OPT       | OPT        | OPT      | OPT       | -        | -                     | -             |
| composer-unused | -     | -         | -          | -        | -         | -        | -                     | OPT           |

REQ = required, OPT = optional, - = not supported

## Структура конфигурации extra.linters (целевая)
```json
{
  "extra": {
    "linters": {
      "phpstan": {
        "paths": ["src", "tests"],
        "skip_dirs": ["vendor", "storage"],
        "skip_files": ["bootstrap/cache/packages.php"],
        "parallel": true,
        "cache_dir": ".cache/phpstan",
        "baseline": "phpstan-baseline.neon"
      },
      "phpcs": {
        "paths": ["src", "tests"],
        "skip_dirs": ["vendor"],
        "skip_files": [],
        "parallel": 4,
        "cache_dir": ".cache"
      },
      "phpmd": {
        "paths": ["src"],
        "skip_dirs": ["vendor"],
        "skip_files": [],
        "baseline": "phpmd-baseline.xml"
      },
      "rector": {
        "paths": ["src", "tests"],
        "skip_dirs": ["vendor"],
        "skip_files": [],
        "parallel": {
          "enabled": true,
          "timeout": 120,
          "max_processes": 8,
          "files_per_process": 20
        },
        "cache_dir": ".cache/rector",
        "frameworks": ["laravel"]
      },
      "php-cs-fixer": {
        "paths": ["src", "tests"],
        "skip_dirs": ["vendor"],
        "skip_files": ["*.blade.php"],
        "parallel": true,
        "cache_dir": ".cache"
      },
      "composer-unused": {
        "named-filters": ["php", "ext-*"]
      }
    }
  }
}
```
