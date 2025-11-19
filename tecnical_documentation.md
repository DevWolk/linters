# Структурированное техническое задание на создание PHP-библиотеки линтеров

## 1. Общая информация о проекте

### 1.1 Название и назначение
- **Название**: `devwolk/linters`
- **Тип**: PHP Composer-библиотека
- **Назначение**: Централизованное управление конфигурациями линтеров и статических анализаторов для PHP-проектов

### 1.2 Основные цели
1. Предоставить единые конфигурации для всех линтеров
2. Обеспечить динамическую настройку через `composer.json > extra`
3. Включить библиотеку кастомных Rector rules
4. Предоставить документацию по использованию всех инструментов
5. Заменить существующий черновик полноценной универсальной библиотекой

---

## 2. Поддерживаемые инструменты

### 2.1 Обязательные инструменты (приоритет 1)
1. **Rector** (^2.0) - автоматический рефакторинг и обновление кода
2. **PHP-CS-Fixer** (^3.8.0) - форматирование кода по стандартам
3. **PHPStan** (^2.1) - статический анализ типов
4. **PHP_CodeSniffer** (^3.12) - проверка стандартов кодирования

### 2.2 Дополнительные инструменты (приоритет 2)
1. **Psalm** (^5.0) - альтернативный статический анализатор, пример конфигурации не php файлов PsalmConfigGenerator.
2. **PHPMD** (@stable) - детектор "code smells"
3. **composer-unused** (^0.9) - обнаружение неиспользуемых зависимостей

### 2.3 Расширения для PHPStan
- `phpstan/extension-installer` (^1.4)
- `phpstan/phpstan-mockery` (^2.0)
- `phpstan/phpstan-phpunit` (^2.0)
- `phpstan/phpstan-strict-rules` (^2.0)
- `thecodingmachine/phpstan-safe-rule` (^1.4)

### 2.4 Дополнительные стандарты кодирования
- `slevomat/coding-standard` (^8.15) - расширенные правила для PHP_CodeSniffer
- `roave/security-advisories` (dev-latest) - проверка безопасности зависимостей

---

## 3. Архитектура библиотеки

### 3.1 Структура директорий
```
devwolk-linters/
├── src/
│   ├── Utils/
│   │   └── ConfigurationLoader.php          # Чтение composer.json > extra
│   ├── Rector/
│   │   ├── Set/
│   │   │   └── AppRectorSetList.php         # Регистрация наборов правил
│   │   └── Rules/                           # Кастомные Rector rules
│   │       ├── AssertInstanceToStaticCallRector.php
│   │       └── MockObjectStaticToInstanceCallRector.php
│   └── ConfigGenerator/                      # Генераторы конфигов
│       ├── PsalmConfigGenerator.php          # Генератор psalm.xml
│       ├── PhpCsConfigGenerator.php          # Генератор phpcs.xml
│       ├── PhpMdConfigGenerator.php          # Генератор phpmd.ruleset.xml
│       └── PhpStanConfigGenerator.php        # Генератор phpstan.neon
├── config/
│   ├── rector.php                           # Базовый конфиг Rector
│   ├── php-cs-fixer.php                     # Базовый конфиг PHP-CS-Fixer
│   ├── phpstan.neon                         # Базовый конфиг PHPStan
│   ├── psalm.xml                            # Шаблон конфига Psalm
│   ├── phpcs.xml                            # Базовый конфиг PHP_CodeSniffer
│   ├── phpmd.xml                            # Базовый конфиг PHPMD
│   └── composer-unused.php                  # Конфиг composer-unused
├── docs/
│   ├── INSTALLATION.md                      # Установка и первая настройка
│   ├── CONFIGURATION.md                     # Детальная конфигурация
│   ├── RECTOR_GUIDE.md                      # Полное руководство по Rector
│   ├── CUSTOM_RECTOR_RULES.md               # Документация кастомных правил
│   ├── PHPSTAN_GUIDE.md                     # Руководство по PHPStan
│   └── TROUBLESHOOTING.md                   # Решение проблем
├── examples/
│   ├── laravel/                             # Пример для Laravel
│   └── symfony/                             # Пример для Symfony
├── tests/
│   ├── Unit/
│   └── Integration/
├── .gitignore
├── composer.json
└── README.md                                # Главная документация
```

### 3.2 Ключевые компоненты

#### ConfigurationLoader (замена ComposerLoader)
```php
namespace Linters\Utils;

class ConfigurationLoader
{
    // Чтение composer.json из корня проекта
    // Поддержка dot-notation для доступа к настройкам
    // Конвертация относительных путей в абсолютные
    // Поддержка дефолтных значений
    // Валидация структуры extra.linters
}
```

#### AppRectorSetList
```php
namespace Linters\Rector\Set;

final class AppRectorSetList
{
    /**@var string */
    public const APP_RULES = __DIR__ . '/../Rules/app-rules.php';

    /**@var string */
    public const STRICT_TYPES = __DIR__ . '/../Rules/strict-types.php';

    /**@var string */
    public const DEPRECATED_METHODS = __DIR__ . '/../Rules/deprecated-methods.php';
    // ... другие наборы
}
```

---

## 4. Механизм конфигурации через composer.json

### 4.1 Структура секции extra.linters

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/src", "/tests"],
        "skip": ["/src/Legacy"],
        "php-version": "8.2",
        "sets": ["code-quality", "type-declarations"]
      },
      "php-cs-fixer": {
        "paths": ["/src", "/config"],
        "skip": ["/vendor", "*.blade.php"],
        "ruleset": ["PSR12"],
        "risky": true
      },
      "phpstan": {
        "paths": ["/src"],
        "level": 8,
        "extensions": ["mockery", "phpunit"],
        "baseline": "phpstan-baseline.neon"
      },
      "psalm": {
        "paths": ["/src"],
        "skip": ["/vendor"],
        "level": 7,
        "plugins": ["Psalm\\LaravelPlugin\\Plugin"]
      },
      "phpcs": {
        "paths": ["/src"],
        "standard": "PSR12",
        "extensions": ["slevomat"]
      },
      "phpmd": {
        "paths": ["/src"],
        "rulesets": ["cleancode", "codesize"]
      }
    }
  }
}
```

### 4.2 Дефолтные значения (если extra.linters не указан)

```php
[
    'rector' => [
        'paths' => ['/src'],
        'skip' => [],
        'php-version' => '8.2',
    ],
    'php-cs-fixer' => [
        'paths' => ['/src'],
        'skip' => [],
    ],
    'phpstan' => [
        'paths' => ['/src'],
        'level' => 8,
    ],
    // ...
]
```

---

## 5. Конфигурационные файлы линтеров

### 5.1 Rector (config/rector.php)

**Требования:**
- Использовать новый API `RectorConfig::configure()` (Rector 2.0+)
- Поддержка динамических путей из `extra.linters.rector.paths`
- Автоматическая регистрация кастомных правил из `AppRectorSetList`
- Настройка параллелизма и кеширования
- Гибкая система skip-правил

**Базовые наборы правил:**
- `LevelSetList::UP_TO_PHP_82`
- `SetList::CODE_QUALITY`
- `SetList::CODING_STYLE`
- `SetList::TYPE_DECLARATION`
- `SetList::EARLY_RETURN`
- `SetList::STRICT_BOOLEANS`

**Кастомные правила (удаляемые из skip):**
- ✅ `TypedPropertyRector`
- ✅ `PropertyTypeDeclarationRector`
- ✅ `RemoveUnusedPrivatePropertyRector`

**Проблемные правила (в skip по умолчанию):**
```php
'skip' => [
    RestoreDefaultNullToNullableTypePropertyRector::class,
    RemoveExtraParametersRector::class,          // ломает dump()
    SplitGroupedUseImportsRector::class,        // конфликт с traits
    CallableThisArrayToAnonymousFunctionRector::class, // ломает Router
    ClosureToArrowFunctionRector::class,        // ломает Router
    CountOnNullRector::class,                   // много false positives
    // ...
]
```

### 5.2 PHP-CS-Fixer (config/php-cs-fixer.php)

**Требования:**
- Базовый ruleset: `@PSR12`
- Поддержка путей из `extra.linters.php-cs-fixer`
- Обнаружение VSCode (отключение Finder для производительности)
- Параллельная обработка файлов, включаемая только по условию из `extra`, по умолчанию отключена
- Риски разрешены (`setRiskyAllowed(true)`)

**Ключевые правила:**
```php
[
    'declare_strict_types' => true,
    'strict_comparison' => true,
    'strict_param' => true,
    'ordered_imports' => ['imports_order' => ['class', 'function', 'const']],
    'no_unused_imports' => true,
    'concat_space' => ['spacing' => 'one'],
    'array_syntax' => ['syntax' => 'short'],
    'phpdoc_align' => ['align' => 'vertical'],
    'no_superfluous_phpdoc_tags' => true,
    'yoda_style' => false,
    // ...
]
```

**Специальная обработка:**
- Игнорирование `*.blade.php`
- Обнаружение VSCode через `$_SERVER['VSCODE_AGENT_FOLDER']`

### 5.3 PHPStan (config/phpstan.neon)

**Требования:**
- Шаблонный файл для динамической генерации
- Генератор `PhpStanConfigGenerator`
- Уровень строгости по умолчанию: `level: 8`
- Поддержка путей из `extra.linters.phpstan`
- Подключение расширений через `extension-installer`
- Возможность переопределения в проектном файле
- Поддержка baseline-файлов добавляемых из `extra.linters.phpstan.baseline` или автоматически из корня проекта

**Базовая структура:**
```neon
parameters:
    level: 8
    paths:
        - %rootDir%/../../src  # динамически из extra
    
    # Расширения
    strictRules:
        allRules: true
    
    # Игнорирование
    excludePaths:
        - %rootDir%/../../vendor
    
    # Настройки
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: true
```

### 5.4 Psalm (config/psalm.xml)

**Требования:**
- Шаблонный файл для динамической генерации
- Генератор `PsalmConfigGenerator` (замена `psalm_config.php`)
- Уровень строгости: `errorLevel="7"`
- Динамическое добавление плагинов

**Структура шаблона:**
```xml
<?xml version="1.0"?>
<psalm errorLevel="7" resolveFromConfigFile="true">
    <projectFiles>
        <!-- Добавляется из extra.linters.psalm.paths -->
        <ignoreFiles>
            <!-- Добавляется из extra.linters.psalm.skip -->
        </ignoreFiles>
    </projectFiles>
    
    <plugins>
        <!-- Добавляется из extra.linters.psalm.plugins -->
    </plugins>
    
    <issueHandlers>
        <!-- handlers, большинство в режиме error -->
    </issueHandlers>
</psalm>
```

### 5.5 PHP_CodeSniffer (config/phpcs.xml)

**Требования:**
- Шаблонный файл для динамической генерации
- Генератор `PhpCsConfigGenerator`
- Базовый стандарт: `PSR12`
- Интеграция `Slevomat\Coding\Standard`
- Поддержка путей из `extra.linters.phpcs`

### 5.6 PHPMD (config/phpmd.ruleset.xml)

**Требования:**
- Шаблонный файл для динамической генерации
- Генератор `PhpMdConfigGenerator`
- Базовые rulesets: `cleancode`, `codesize`, `controversial`, `design`, `naming`, `unusedcode`
- Поддержка baseline-файлов добавляемых из `extra.linters.phpmd.baseline` или автоматически из корня проекта

### 5.7 composer-unused (config/composer-unused.php)

**Требования:**
- Фильтры для системных зависимостей
- Возможность расширения из проекта

---

## 6. Кастомные Rector Rules

### 6.1 Список обязательных правил
- `AssertInstanceToStaticCallRector` - преобразование PHPUnit assertions
- `MockObjectStaticToInstanceCallRector` - рефакторинг mock-объектов

### 6.2 Структура правил

```php
namespace Linters\Rector\Rules;

use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class DeclareStrictTypesRector extends AbstractRector implements ConfigurableRectorInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Adds declare(strict_types=1) to every PHP file',
            [/* примеры */]
        );
    }
    
    public function getNodeTypes(): array { /* ... */ }
    public function refactor(Node $node): ?Node { /* ... */ }
    public function configure(array $configuration): void { /* ... */ }
}
```

### 6.3 Регистрация правил через AppRectorSetList

```php
// config/rector.php
use Linters\Rector\Set\AppRectorSetList;

return RectorConfig::configure()
    ->withSets([
        AppRectorSetList::APP_RULES,
        AppRectorSetList::STRICT_TYPES,
    ])
    // ...
```

---

## 7. Документация

### 7.1 README.md (главная страница)

**Содержание:**
1. Краткое описание библиотеки
2. Требования в виде таблицы (PHP 8.2+, Composer 2.0+, etc.)
3. Список поддерживаемых инструментов
4. Быстрый старт (3 команды)
5. Ссылки на детальную документацию по каждому инструменту

### 7.2 docs/INSTALLATION.md

**Содержание:**
1. Установка через Composer
2. Настройка `composer.json > extra.linters`
3. Добавление composer scripts
4. Первый запуск каждого инструмента
5. Проверка корректности установки

### 7.3 docs/CONFIGURATION.md

**Содержание:**
1. Полное описание структуры `extra.linters`
2. Дефолтные значения для каждого инструмента
3. Примеры конфигураций для разных сценариев
4. Переопределение базовых конфигов
5. Создание проектных конфигов (extends)

### 7.4 docs/RECTOR_GUIDE.md

**Содержание:**
1. Что такое Rector и зачем он нужен
2. Базовые команды:
   - `vendor/bin/rector process` - применение изменений
   - `vendor/bin/rector process --dry-run` - проверка без изменений
   - `vendor/bin/rector list` - список доступных правил
3. Миграции между версиями PHP:
    ```bash
    # PHP 8.0 → 8.2
    rector process --set php82
    ```
4. Миграции фреймворков (Laravel, Symfony)
5. Работа с кастомными правилами
6. Создание собственных rules
7. Отладка и troubleshooting

### 7.5 docs/CUSTOM_RECTOR_RULES.md

**Содержание:**
1. Список всех кастомных Rector rules
2. Детальное описание каждого правила:
   - Назначение
   - Примеры "было/стало"
   - Конфигурация
   - Ограничения
3. Создание собственных правил

### 7.6 docs/PHPSTAN_GUIDE.md

**Содержание:**
1. Введение в PHPStan
2. Уровни строгости (0-9)
3. Работа с baseline:
    ```bash
    vendor/bin/phpstan analyze --generate-baseline
    ```
4. Настройка расширений
5. Игнорирование ошибок

---

## 8. Composer scripts

### 8.1 Базовые скрипты для библиотеки

```json
{
  "scripts": {
    "rector": "rector process --config=config/rector.php --clear-cache",
    "rector-check": "rector process --config=config/rector.php --dry-run",
    "php-cs-fixer": "php-cs-fixer fix --config=config/php-cs-fixer.php --allow-risky=yes",
    "php-cs-fixer-check": "php-cs-fixer fix --config=config/php-cs-fixer.php --dry-run --diff",
    "phpstan": "phpstan analyze --configuration=config/phpstan.neon",
    "phpstan-baseline": "phpstan analyze --configuration=config/phpstan.neon --generate-baseline",
    "psalm": "psalm --config=config/psalm.xml --no-cache",
    "phpcs": "phpcs --standard=config/phpcs.xml",
    "phpmd": "phpmd src text config/phpmd.xml",
    "composer-unused": "composer-unused --configuration=config/composer-unused.php"
  }
}
```

### 8.2 Скрипты для проекта-потребителя

```json
{
  "scripts": {
    "rector": "rector process --config=vendor/devwolk/linters/config/rector.php",
    "rector-check": "rector process --config=vendor/devwolk/linters/config/rector.php --dry-run",
    "php-cs-fixer": "php-cs-fixer fix --config=vendor/devwolk/linters/config/php-cs-fixer.php",
    "php-cs-fixer-check": "php-cs-fixer fix --config=vendor/devwolk/linters/config/php-cs-fixer.php --dry-run",
    "phpstan": "phpstan analyze --configuration=vendor/devwolk/linters/config/phpstan.neon"
  }
}
```

---

## 9. Примеры интеграции (директория examples/)

### 9.1 laravel/ - Настройка для Laravel

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/app", "/config", "/database", "/tests"],
        "skip": ["/app/Http/Middleware", "/bootstrap"],
        "sets": ["laravel-120"]
      },
      "php-cs-fixer": {
        "paths": ["/app", "/config", "/database"],
        "skip": ["*.blade.php"]
      },
      "phpstan": {
        "paths": ["/app"],
        "level": 8,
        "extensions": ["larastan"]
      },
      "psalm": {
        "plugins": ["Psalm\\LaravelPlugin\\Plugin"]
      }
    }
  }
}
```

### 9.2 symfony/ - Настройка для Symfony

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/src", "/tests"],
        "sets": ["symfony-64"]
      },
      "phpstan": {
        "extensions": ["symfony", "doctrine"]
      }
    }
  }
}
```

---

## 10. Известные проблемы и план миграции

### 10.1 Проблемы текущего черновика (code/)

1. **Отсутствующая функциональность:**
   - PHPStan не поддерживается (только Psalm)
   - PHP_CodeSniffer не поддерживается
   - PHPMD не поддерживается
   - Нет CLI runner'а (TODO в README)
   - Нет тестов

### 10.2 План миграции

#### Этап 1: Рефакторинг конфигов
- Убрать hardcoded skip-правила в отдельную конфигурацию
- Создать универсальные дефолты

#### Этап 3: Добавление PHPStan
- Создать `config/phpstan.neon`
- Интегрировать с `ConfigurationLoader`
- Документировать использование

#### Этап 4: Кастомные Rector Rules
- Создать `AppRectorSetList`

#### Этап 5: Документация
- Переписать README
- Создать полную документацию в `docs/`
- Добавить примеры в `examples/`

#### Этап 6: Тестирование
- Unit-тесты для `ConfigurationLoader`
- Integration-тесты для конфигов
- Тесты для кастомных Rector rules

#### Этап 7: CLI Runner
- Создать единую команду запуска всех линтеров
- Добавить опции для выборочного запуска
- Интегрировать с composer scripts

---

## 11. Требования к реализации

### 11.1 Технические требования
- PHP 8.2+
- Composer 2.0+
- Поддержка Unix/Linux/macOS
- PSR-4 autoloading
- Semantic Versioning

### 11.2 Требования к качеству кода
- Покрытие тестами ≥ 80%
- PHPStan level 8 без ошибок
- Соответствие PSR-12
- Документированность всех public методов (PHPDoc)

### 11.3 Требования к документации
- Примеры для каждого сценария использования
- Диаграммы архитектуры (опционально)

### 11.4 Требования к совместимости
- Работа с Laravel 10+
- Работа с Symfony 6+

---

## 12. Итоговый чеклист

### Код
- [ ] Обновить `composer.json` с актуальными зависимостями
- [ ] Создать `ConfigurationLoader` с полным функционалом
- [ ] Создать конфиги для всех 7 инструментов
- [ ] Реализовать минимум 2 кастомных Rector rules
- [ ] Создать `AppRectorSetList` с регистрацией правил
- [ ] Создать `PsalmConfigGenerator` (замена `psalm_config.php`)
- [ ] Создать `PhpStanConfigGenerator`
- [ ] Создать `PhpCsConfigGenerator`
- [ ] Создать `PhpMdConfigGenerator`
- [ ] Интегрировать все компоненты

### Документация
- [ ] `README.md` с актуальной информацией
- [ ] `docs/INSTALLATION.md`
- [ ] `docs/CONFIGURATION.md`
- [ ] `docs/RECTOR_GUIDE.md`
- [ ] `docs/PHPSTAN_GUIDE.md`
- [ ] `docs/CUSTOM_RULES.md`
- [ ] `docs/TROUBLESHOOTING.md`

### Примеры
- [ ] `examples/laravel/`
- [ ] `examples/symfony/`

### Тестирование
- [ ] Unit-тесты для `ConfigurationLoader`
- [ ] Integration-тесты для конфигов
- [ ] Тесты для Rector rules

### Дополнительно
- [ ] `.gitignore` с исключением артефактов
- [ ] Подготовка к публикации на Packagist

---

## 13. Ожидаемый результат

После выполнения всех задач должна получиться **production-ready** PHP-библиотека, которая:

1. ✅ Устанавливается одной командой `composer require --dev devwolk/linters`
2. ✅ Настраивается через `composer.json > extra.linters` с минимальными усилиями
3. ✅ Предоставляет единые конфигурации для 7 инструментов статического анализа
4. ✅ Содержит библиотеку готовых кастомных Rector rules
5. ✅ Имеет исчерпывающую документацию для всех сценариев
6. ✅ Легко интегрируется в CI/CD пайплайны
7. ✅ Работает из коробки с Laravel, Symfony
8. ✅ Позволяет расширение и переопределение настроек
9. ✅ Покрыта тестами и готова к maintenance
10. ✅ Опубликована на Packagist и готова к использованию