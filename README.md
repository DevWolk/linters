# devwolk/linters

Централизованные конфигурации линтеров для PHP проектов.
Все правила зашиты в библиотеку — пользователи настраивают только пути и базовые параметры.

## Установка

```bash
composer require --dev devwolk/linters
```

**Требования:** PHP 8.2+, Composer 2.0+

## Поддерживаемые инструменты

| Инструмент          | Описание                                             |
|---------------------|------------------------------------------------------|
| **Rector**          | Автоматический рефакторинг, миграция PHP/фреймворков |
| **PHP-CS-Fixer**    | Форматирование кода (PSR-12 + strict rules)          |
| **PHPStan**         | Статический анализ (level 8)                         |
| **PHPCS**           | Code style проверки (PSR-12 + Slevomat)              |
| **PHPMD**           | Детектор проблем качества кода                       |
| **composer-unused** | Поиск неиспользуемых зависимостей                    |

## Конфигурация

Вся конфигурация в `composer.json` под ключом `extra.linters`:

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["src", "tests"],
        "skip_dirs": ["vendor"],
        "skip_files": [],
        "frameworks": ["laravel"],
        "parallel": {
          "enabled": true,
          "timeout": 120,
          "max_processes": 4,
          "files_per_process": 20
        },
        "cache_dir": ".cache/rector"
      },
      "php-cs-fixer": {
        "paths": ["src"],
        "skip_dirs": ["src/Legacy"],
        "skip_files": ["*.blade.php"],
        "parallel": true,
        "cache_dir": ".cache/php-cs-fixer"
      },
      "phpstan": {
        "paths": ["src"],
        "skip_dirs": ["vendor"],
        "skip_files": [],
        "parallel": true,
        "cache_dir": ".cache/phpstan",
        "baseline": "phpstan-baseline.neon"
      },
      "phpcs": {
        "paths": ["src"],
        "skip_dirs": [],
        "skip_files": [],
        "parallel": 4,
        "cache_dir": ".cache/phpcs"
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

### Поддерживаемые опции

| Опция           | Тип                 | Инструменты                          | Описание                                |
|-----------------|---------------------|--------------------------------------|-----------------------------------------|
| `paths`         | `string[]`          | все кроме composer-unused            | **Обязательно.** Директории для анализа |
| `skip_dirs`     | `string[]`          | все кроме composer-unused            | Директории для исключения               |
| `skip_files`    | `string[]`          | все кроме composer-unused            | Паттерны файлов для исключения          |
| `parallel`      | `bool\|int\|object` | rector, php-cs-fixer, phpstan, phpcs | Параллельное выполнение                 |
| `cache_dir`     | `string`            | rector, php-cs-fixer, phpstan, phpcs | Директория кэша                         |
| `baseline`      | `string`            | phpstan, phpmd                       | Файл baseline для игнорирования ошибок  |
| `frameworks`    | `string[]`          | rector                               | Пресет фреймворка: `laravel`            |
| `named-filters` | `string[]`          | composer-unused                      | Пакеты для игнорирования                |

### Формат parallel

```json
// Простое включение
"parallel": true

// Количество процессов
"parallel": 4

// Полная конфигурация
"parallel": {
  "enabled": true,
  "timeout": 120,
  "max_processes": 8,
  "files_per_process": 20
}
```

## Использование

### CLI

```bash
# Запуск инструментов
./vendor/bin/linters run rector
./vendor/bin/linters run php-cs-fixer
./vendor/bin/linters run phpstan
./vendor/bin/linters run phpcs
./vendor/bin/linters run phpmd
./vendor/bin/linters run composer-unused

# Генерация конфигов (phpstan/phpcs/phpmd)
./vendor/bin/linters generate phpstan
```

### Composer scripts

Добавьте в `composer.json`:

```json
{
  "scripts": {
    "rector": "./vendor/bin/rector process --config=./vendor/devwolk/linters/configs/rector.php --clear-cache",
    "rector-check": "./vendor/bin/rector process --config=./vendor/devwolk/linters/configs/rector.php --dry-run",
    "php-cs-fixer": "./vendor/bin/php-cs-fixer fix --config=./vendor/devwolk/linters/configs/.php-cs-fixer.dist.php --allow-risky=yes",
    "php-cs-fixer-check": "./vendor/bin/php-cs-fixer fix --dry-run --config=./vendor/devwolk/linters/configs/.php-cs-fixer.dist.php --diff",
    "phpstan": "./vendor/bin/linters run phpstan",
    "phpstan-baseline": ["./vendor/bin/linters generate phpstan", "./vendor/bin/phpstan analyze --configuration=./phpstan.neon --generate-baseline"],
    "phpcs": "./vendor/bin/linters run phpcs",
    "phpmd": "./vendor/bin/linters run phpmd",
    "composer-unused": "./vendor/bin/composer-unused --configuration=./vendor/devwolk/linters/configs/composer-unused.php",
    "lint": ["@rector-check", "@php-cs-fixer-check", "@phpstan", "@phpcs"],
    "lint-fix": ["@rector", "@php-cs-fixer"]
  }
}
```

## Архитектура

```
bin/linters              CLI (Symfony Console)
    ↓
ConfigurationLoader      Читает extra.linters из composer.json
    ↓
DTO                      RectorConfig, PhpStanConfig, etc.
    ↓
┌───────────────────────────────────────────────────┐
│ Генерируемые конфиги   │ Динамические конфиги     │
│ (пишутся в корень)     │ (из пакета)              │
├────────────────────────┼──────────────────────────┤
│ phpstan.neon           │ configs/rector.php       │
│ phpcs.xml              │ configs/.php-cs-fixer.dist.php │
│ phpmd.ruleset.xml      │ configs/composer-unused.php │
└───────────────────────────────────────────────────┘
```

### Как работает

1. **ConfigurationLoader** читает `extra.linters` из composer.json проекта
2. Создаёт типизированные DTO для каждого инструмента
3. **Генерируемые конфиги** (phpstan, phpcs, phpmd): ConfigGenerator создаёт файл в корне проекта на основе шаблона + DTO
4. **Динамические конфиги** (rector, php-cs-fixer, composer-unused): конфиг из пакета сам читает DTO при загрузке

## Laravel

Для Laravel проектов добавьте `frameworks: ["laravel"]`:

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

Требуется: `composer require --dev rector/rector-laravel`

## Baseline (игнорирование существующих ошибок)

Для legacy проектов с большим количеством ошибок:

```bash
# PHPStan
./vendor/bin/linters generate phpstan
./vendor/bin/phpstan analyze --configuration=./phpstan.neon --generate-baseline

# Добавить в конфиг
"phpstan": {
  "paths": ["src"],
  "baseline": "phpstan-baseline.neon"
}
```

## Кастомные Rector правила

Библиотека включает два кастомных правила для PHPUnit:

### MockObjectStaticToInstanceCallRector

```php
// Before
$mock->expects(self::once())->method('foo');

// After
$mock->expects($this->once())->method('foo');
```

### AssertInstanceToStaticCallRector

```php
// Before
$this->assertTrue($value);

// After
self::assertTrue($value);
```

## Troubleshooting

### Memory limit

```bash
php -d memory_limit=2G vendor/bin/phpstan analyze
```

### Очистка кэша

```bash
./vendor/bin/rector process --clear-cache
rm -rf .php-cs-fixer.cache .phpcs-cache
```

### Конфигурация не загружается

1. Проверьте JSON: `composer validate`
2. Убедитесь что конфиг в корневом composer.json
3. `composer dump-autoload`

## Примеры

См. директорию `examples/` для полных примеров конфигурации Laravel и Symfony проектов.
