# TODO

## Карта проекта

### Архитектура

```
bin/linters                    → CLI entrypoint (Symfony Console Application)
│
├── src/Console/Application.php       → Регистрирует GenerateConfigCommand, RunCommand
├── src/Console/Command/
│   ├── GenerateConfigCommand.php     → `linters generate <tool>` (phpstan|phpcs|phpmd)
│   └── RunCommand.php                → `linters run <tool>` (все инструменты)
│
├── src/Utils/
│   ├── ConfigurationLoader.php       → Читает extra.linters из composer.json
│   └── ConfigValidation.php          → Валидация и нормализация значений конфигурации
│
├── src/Enum/Tool.php                  → Enum всех поддерживаемых инструментов
│
├── src/DTO/
│   ├── ToolConfigInterface.php        → Интерфейс fromArray()
│   ├── BaseToolConfig.php             → Базовый класс: paths, skipDirs, skipFiles, parallel, cacheDir, baseline
│   ├── ParallelConfig.php             → enabled, timeout, maxProcesses, filesPerProcess
│   ├── RectorConfig.php               → + frameworks (laravel, symfony)
│   ├── PhpStanConfig.php              → + baseline
│   ├── PhpCsConfig.php                → стандартный
│   ├── PhpMdConfig.php                → + baseline, без parallel/cache
│   ├── PhpCsFixerConfig.php           → стандартный
│   └── ComposerUnusedConfig.php       → только namedFilters
│
├── src/ConfigGenerator/
│   ├── ConfigGeneratorInterface.php   → generate(string $targetPath): void
│   ├── PhpStanConfigGenerator.php     → Генерирует phpstan.neon
│   ├── PhpCsConfigGenerator.php       → Генерирует phpcs.xml
│   └── PhpMdConfigGenerator.php       → Генерирует phpmd.ruleset.xml
│
├── src/Service/ToolRunner.php         → Оркестрация: generate + build command + passthru
│
├── src/Rector/
│   ├── Set/AppRectorSetList.php       → Константы путей к кастомным sets
│   ├── Configs/Sets/
│   │   ├── app-rules.php              → Регистрация кастомных Rector rules
│   │   ├── doctrine.php               → Doctrine-специфичные правила
│   │   └── laravel.php                → Laravel sets (если rector-laravel установлен)
│   └── Rules/
│       ├── MockObjectStaticToInstanceCallRector.php → self::any() → $this->any()
│       └── AssertInstanceToStaticCallRector.php     → $this->assert*() → self::assert*()
│
└── configs/
    ├── rector.php                     → Динамический конфиг, читает DTO
    ├── .php-cs-fixer.dist.php         → Динамический конфиг, читает DTO
    ├── composer-unused.php            → Динамический конфиг, читает DTO
    ├── phpstan.neon                   → Шаблон для генератора
    ├── phpcs.xml                      → Шаблон для генератора
    └── phpmd.ruleset.xml              → Шаблон для генератора
```

### Поток выполнения

```
Пользователь запускает: ./vendor/bin/linters run phpstan
    ↓
RunCommand → ConfigurationLoader (читает composer.json)
    ↓
ToolRunner::generate() → PhpStanConfigGenerator
    ↓
Генерируется phpstan.neon в корне проекта
    ↓
ToolRunner::buildPhpStanCommand() → phpstan analyze --configuration=./phpstan.neon
    ↓
passthru() → вывод и exit code
```

### Поддерживаемые ключи конфигурации

| Tool            | paths | skip_dirs | skip_files | parallel | cache_dir | baseline | frameworks | named-filters |
|-----------------|:-----:|:---------:|:----------:|:--------:|:---------:|:--------:|:----------:|:-------------:|
| rector          |  REQ  | OPT       | OPT        | OPT*     | OPT       | -        | OPT        | -             |
| php-cs-fixer    |  REQ  | OPT       | OPT        | OPT      | OPT       | -        | -          | -             |
| phpstan         |  REQ  | OPT       | OPT        | OPT      | OPT       | OPT      | -          | -             |
| phpcs           | REQ   | OPT       | OPT        | OPT      | OPT       | -        | -          | -             |
| phpmd           | REQ   | OPT       | OPT        | -        | -         | OPT      | -          | -             |
| composer-unused | -     | -         | -          | -        | -         | -        | -          | OPT           |

REQ = required, OPT = optional, - = not supported
*rector parallel по умолчанию enabled

---
