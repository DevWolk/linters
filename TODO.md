# TODO: План улучшений devwolk/linters

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
