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
├── configs/                                 
│   ├── rector.php                           # Базовый конфиг Rector
│   ├── .php-cs-fixer.dist.php               # Базовый конфиг PHP-CS-Fixer
│   ├── phpstan.neon                         # Базовый конфиг PHPStan
│   ├── psalm_default.xml                    # Шаблон конфига Psalm
│   ├── phpcs.xml                            # Базовый конфиг PHP_CodeSniffer
│   ├── phpmd.ruleset.xml                    # Базовый конфиг PHPMD
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
├── Makefile                                  # Команды для разработки
├── .gitignore
├── composer.json
└── README.md                                # Главная документация
```

**Важные замечания по структуре:**
- Namespace библиотеки: `Linters\`
- Конфиги в директории `configs/`

### 3.2 Ключевые компоненты

#### ConfigurationLoader
```php
namespace Linters\Utils;

/**
 * Класс для чтения настроек из composer.json проекта
 *
 * Реализованный функционал:
 * - Чтение composer.json из корня проекта (getcwd())
 * - Поддержка dot-notation для доступа к вложенным настройкам
 * - Конвертация относительных путей в абсолютные
 * - Поддержка дефолтных значений при отсутствии настройки
 * - Настраиваемый ключ в extra (по умолчанию 'linters')
 * - JSON парсинг с обработкой ошибок
 */
class ConfigurationLoader
{
    protected const COMPOSER_FILE = '/composer.json';
    protected array $config = [];

    public function __construct(
        protected ?string $composerDir = null,
        protected string $extraKey = 'linters',
    ) {
        // Реализация...
    }

    /**
     * Получить значение по ключу с поддержкой dot-notation
     * @example get('rector.paths', [])
     */
    public function get(string $keys, mixed $default = null): array|string|null

    /**
     * Получить массив абсолютных путей
     * @example getAbsolutePaths('rector.paths') → ['/app/src', '/app/tests']
     */
    public function getAbsolutePaths(string $key, array $default = []): array
}
```

#### PsalmConfigGenerator (CLI-скрипт)
```php
/**
 * CLI-скрипт для динамической генерации psalm.xml
 * Файл: src/ConfigGenerator/PsalmConfigGenerator.php
 *
 * Возможности:
 * - Чтение базового шаблона из configs/psalm_default.xml
 * - Динамическое добавление путей из extra.linters.psalm.paths
 * - Добавление skip-паттернов из extra.linters.psalm.skip
 * - Рекурсивная обработка nested конфигов (plugins, issueHandlers)
 * - Поддержка XPath для сложных манипуляций с XML
 * - Форматирование итогового XML с отступами
 * - Предотвращение дублирования элементов через сравнение атрибутов
 *
 * Использование:
 * php src/ConfigGenerator/PsalmConfigGenerator.php --target=./psalm.xml
 *
 * Аргументы:
 * --target (-t) - путь для сохранения сгенерированного конфига
 * --config (-c) - путь к базовому шаблону (опционально, используется для будущего расширения)
 *
 * Процесс генерации:
 * 1. Загрузка psalm_default.xml через SimpleXMLElement
 * 2. Добавление <directory> в <projectFiles> из extra.linters.psalm.paths
 * 3. Добавление <directory> в <ignoreFiles> из extra.linters.psalm.skip
 * 4. Рекурсивная обработка extra.linters.psalm.config для plugins и других настроек
 * 5. Форматирование через DOMDocument
 * 6. Сохранение в --target
 *
 * Пример extra.linters.psalm.config:
 * {
 *   "plugins": {
 *     "pluginClass": [
 *       {"class": "Psalm\\LaravelPlugin\\Plugin"}
 *     ]
 *   }
 * }
 */
```

#### AppRectorSetList
```php
namespace Linters\Rector\Set;

/**
 * Константы для подключения наборов кастомных Rector правил
 */
final class AppRectorSetList
{
    /**@var string */
    public const APP_RULES = __DIR__ . '/../Rules/app-rules.php';

    /**@var string */
    public const STRICT_TYPES = __DIR__ . '/../Rules/strict-types.php';

    /**@var string */
    public const DEPRECATED_METHODS = __DIR__ . '/../Rules/deprecated-methods.php';

    // Дополнительные наборы правил по мере необходимости
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

### 5.1 Rector (configs/rector.php)

**Требования:**
- Использовать **современный API Rector 2.0+** (`RectorConfig::configure()`)
- Поддержка динамических путей из `extra.linters.rector.paths` и `extra.linters.rector.skip`
- Автоматическая регистрация кастомных правил из `Linters\Rector\Rules\`
- Настройка параллелизма, кеширования и производительности
- Категоризированная система skip-правил с документацией

**Современный API Rector 2.0+:**
```php
use Rector\Config\RectorConfig;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Linters\Utils\ConfigurationLoader;

$composerLoader = new ConfigurationLoader();

return RectorConfig::configure()
    // Кеширование и производительность
    ->withCache(
        cacheDirectory: '/tmp/rector',           // работает локально и на CI
        cacheClass: FileCacheStorage::class      // file system вместо in-memory
    )
    ->withRootFiles()
    ->withParallel(360, 2, 40)                   // maxProcesses, jobSize, jobTimeout

    // Импорты
    ->withImportNames(
        importDocBlockNames: false,              // не импортировать типы из docblocks
        importShortClasses: false                // не импортировать short classes
    )

    // Подготовленные наборы правил
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
        strictBooleans: true,
        carbon: true,
        rectorPreset: true,
        phpunitCodeQuality: true,
        doctrineCodeQuality: true,
        symfonyCodeQuality: true,
        symfonyConfigs: false,                   // отключено для совместимости
    )

    // Composer-based наборы (автоматически активируются при наличии зависимостей)
    ->withComposerBased(
        doctrine: true,
        phpunit: true,
        symfony: true,
    )

    // Миграция атрибутов
    ->withAttributesSets(
        doctrine: true,
        mongoDb: true,
        gedmo: true,
        phpunit: true,
    )

    // Кастомные правила
    ->withRules([
        MockObjectStaticToInstanceCallRector::class,
        AssertInstanceToStaticCallRector::class,
    ])

    // Специфичные наборы
    ->withSets([
        PHPUnitSetList::PHPUNIT_90,
        PHPUnitSetList::PHPUNIT_100,
        PHPUnitSetList::PHPUNIT_110,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,

        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        SetList::STRICT_BOOLEANS,

        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,

        LevelSetList::UP_TO_PHP_82,
        LaravelLevelSetList::UP_TO_LARAVEL_110,
        LaravelSetList::LARAVEL_CODE_QUALITY,
    ])

    ->withPhpVersion(PhpVersion::PHP_82)
    ->withPaths($composerLoader->getAbsolutePaths('rector.paths'))
    ->withSkip(/* см. ниже категоризированные skip-правила */)
    ->withFileExtensions(['php']);
```

**Категории проблемных правил (skip):**

```php
->withSkip(
    array_merge(
        $composerLoader->getAbsolutePaths('rector.skip'),  // пользовательские skip
        [
            // ========================================
            // КРИТИЧНЫЕ - Ломают функциональность
            // ========================================
            SimplifyBoolIdenticalTrueRector::class,
            // Причина: breaks Laravel/Symfony Routers
            // Проблема: упрощает === true, что меняет логику роутинга

            ClosureToArrowFunctionRector::class,
            // Причина: breaks Laravel/Symfony Routers
            // Проблема: конвертация closure → arrow fn меняет scope $this

            SeparateMultiUseImportsRector::class,
            // Причина: breaks using multiple Traits with insteadof
            // Проблема: разделяет grouped use, ломает разрешение конфликтов трейтов

            RemoveExtraParametersRector::class,
            // Причина: удаляет аргументы в dump(), dd(), logger() helpers
            // Проблема: не понимает variadic functions

            // ========================================
            // КОНФЛИКТЫ С ДРУГИМИ ИНСТРУМЕНТАМИ
            // ========================================
            PreferPHPUnitThisCallRector::class,
            // Причина: breaks with PHPStan static analysis
            // Конфликт: PHPStan требует static calls, Rector хочет $this

            // ========================================
            // ЛОМАЮТ ENTITY/DTO/MODEL
            // ========================================
            RestoreDefaultNullToNullableTypePropertyRector::class,
            // Причина: не работает с DTO nullable параметрами
            // Проблема: добавляет = null где не нужно

            RenamePropertyToMatchTypeRector::class,
            // Причина: breaks Doctrine Entities, DTOs
            // Проблема: переименовывает свойства по типу, ломает маппинг БД

            // ========================================
            // ИЗБЫТОЧНЫЕ - Redundant refactoring
            // ========================================
            RenameVariableToMatchMethodCallReturnTypeRector::class,
            RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class,
            RenameForeachValueVariableToMatchExprVariableRector::class,
            // Причина: слишком агрессивное переименование переменных
            // Проблема: снижает читаемость, breaks unit tests

            // ========================================
            // FALSE POSITIVES - Много ложных срабатываний
            // ========================================
            IsCountableRector::class,
            // Причина: слишком много false positives
            // Проблема: добавляет is_countable() где не нужно

            // ========================================
            // BREAKS UNIT TESTS
            // ========================================
            TypedPropertyFromCreateMockAssignRector::class,
            RenameVariableToMatchNewTypeRector::class,
            // Причина: breaks PHPUnit mock objects typing

            // ========================================
            // THINKING - На рассмотрении
            // ========================================
            AddMethodCallBasedStrictParamTypeRector::class,
            // Проблема: breaks multiple Traits usage

            FlipTypeControlToUseExclusiveTypeRector::class,
            IssetOnPropertyObjectToPropertyExistsRector::class,
            RenameParamToMatchTypeRector::class,
            PostIncDecToPreIncDecRector::class,
            // Статус: анализируется целесообразность включения

            // ========================================
            // WAITING FIX - Ожидают исправления в Rector
            // ========================================
            MakeInheritedMethodVisibilitySameAsParentRector::class,
            RemoveParentCallWithoutParentRector::class,
            // Статус: известные баги, ожидается фикс в следующих версиях
        ],
    )
)
```

**Дополнительные индивидуальные правила:**
```php
->withRules([
    // Rector правила, требующие явной активации
    ReplaceFetchAllMethodCallRector::class,              // Doctrine DBAL
    ReplaceLifecycleEventArgsByDedicatedEventArgsRector::class, // Doctrine ORM
])
```

### 5.2 PHP-CS-Fixer (configs/.php-cs-fixer.dist.php)

**Требования:**
- Базовый ruleset: `@PSR12`
- Поддержка путей из `extra.linters.cs-fixer.paths` и `extra.linters.cs-fixer.skip`
- **VSCode оптимизация** - отключение Finder для производительности
- **Опциональный параллелизм** через `extra.linters.cs-fixer.parallel`
- Риски разрешены (`setRiskyAllowed(true)`)
- Игнорирование Blade-шаблонов

**Специальная оптимизация для IDE:**
```php
use Linters\Utils\ConfigurationLoader;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

// VSCode Detection - отключает Finder для ускорения работы в IDE
$isVSCodeRun = isset($_SERVER['VSCODE_AGENT_FOLDER']);
$finder = [];

if ($isVSCodeRun === false) {
    // Finder активируется только при запуске из CLI
    $composerLoader = new ConfigurationLoader();

    $finder = Finder::create()
        ->ignoreVCS(true)
        ->ignoreDotFiles(true)
        ->name('*.php')
        ->notName(['*.blade.php', '_*'])                // игнорируем Blade и helpers
        ->exclude($composerLoader->getAbsolutePaths('cs-fixer.skip'))
        ->in($composerLoader->getAbsolutePaths('cs-fixer.paths'));
}

$config = new Config();

return $config
    ->setRiskyAllowed(true)
    ->setFinder($finder);
```

**Параллельная обработка (опционально):**
```php
// Параллелизм отключен по умолчанию
// Можно включить через extra.linters.cs-fixer.parallel: true

$parallel = $composerLoader->get('cs-fixer.parallel', false);
if ($parallel) {
    $config->setParallelConfig(ParallelConfigFactory::detect());
}

// ParallelConfigFactory::detect() автоматически определяет:
// - Количество доступных CPU cores
// - Оптимальное количество процессов
// - Размер job batch
```

**Ключевые правила:**
```php
->setRules([
    // === ГЛОБАЛЬНЫЕ ===
    '@PSR12' => true,
    'psr_autoloading' => true,
    'single_quote' => true,
    'declare_strict_types' => true,
    'strict_comparison' => true,
    'strict_param' => true,

    // === ИМПОРТЫ ===
    'ordered_imports' => [
        'imports_order' => ['class', 'function', 'const'],
        'sort_algorithm' => 'alpha'
    ],
    'no_unused_imports' => true,
    'fully_qualified_strict_types' => true,
    'native_function_invocation' => [
        'include' => ['@compiler_optimized'],
        'scope' => 'namespaced',
        'strict' => true
    ],

    // === SPACING / ФОРМАТИРОВАНИЕ ===
    'concat_space' => ['spacing' => 'one'],
    'binary_operator_spaces' => [
        'default' => 'at_least_single_space',
        'operators' => ['=>' => 'align_single_space_minimal']  // выравнивание =>
    ],
    'blank_line_before_statement' => [
        'statements' => ['return', 'do', 'exit', 'if', 'switch', 'try']
    ],

    // === МАССИВЫ ===
    'array_syntax' => ['syntax' => 'short'],
    'trim_array_spaces' => true,
    'trailing_comma_in_multiline' => true,
    'array_indentation' => true,

    // === PHPDOCS ===
    'phpdoc_align' => [
        'align' => 'vertical',
        'tags' => ['param', 'property', 'return', 'throws', 'type', 'var']
    ],
    'no_superfluous_phpdoc_tags' => true,
    'phpdoc_separation' => true,

    // === ОТКЛЮЧЕННЫЕ ===
    'yoda_style' => false,           // нормальный порядок сравнения
    'void_return' => false,          // опционально
    'is_null' => false,              // можно использовать === null
])
```

**Специальная обработка файлов:**
- Игнорирование `*.blade.php` (Laravel Blade templates)
- Игнорирование `_*.php` (helper files)
- Обнаружение VSCode через `$_SERVER['VSCODE_AGENT_FOLDER']`
  - В VSCode: Finder не используется (быстрый запуск)
  - В CLI: Finder сканирует директории

**Проблема и решение:**
```
Проблема: PHP-CS-Fixer медленно запускается в VSCode при редактировании файлов
Решение: Отключаем Finder::create() при запуске из IDE
Результат: Мгновенный запуск фиксера в VSCode
```

### 5.3 PHPStan (config/phpstan.neon)

**Требования:**
- Шаблонный файл для динамической генерации
- Генератор `PhpStanConfigGenerator`
- Уровень строгости по умолчанию: `level: 8`
- Поддержка путей из `extra.linters.phpstan.paths` и `extra.linters.phpstan.skip`
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
- `psalm_config.php` после замены удаляется из библиотеки
- Уровень строгости: `errorLevel="7"`
- Динамическое добавление плагинов
- Поддержка путей из `extra.linters.psalm.paths` и `extra.linters.psalm.skip`

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

### 5.5 PHP_CodeSniffer (configs/phpcs.xml)

**Требования:**
- Шаблонный файл для динамической генерации
- Генератор `PhpCsConfigGenerator`
- Базовый стандарт: `PSR12`
- Интеграция `Slevomat\Coding\Standard` (расширенные правила)
- **Документирование каждого правила** с Group/Documentation/Description/Priority
- Настройка метрик сложности (Cyclomatic Complexity, Nesting Level)
- Поддержка путей из `extra.linters.phpcs.paths` и `extra.linters.phpcs.skip`

**Преимущество детального документирования:**
Каждое правило сопровождается комментарием с пояснением, что упрощает понимание конфигурации:
```xml
<!--
    Group: Generic
    Documentation: https://kalimah-apps.com/phpcs/docs/rules/Generic/Metrics.CyclomaticComplexity.html
    Description: Measures the number of linearly independent paths through a function's source code.
    Priority: Medium
-->
<rule ref="Generic.Metrics.CyclomaticComplexity">
    <properties>
        <property name="complexity" value="7"/>
        <property name="absoluteComplexity" value="14"/>
    </properties>
</rule>
```

**Интеграция Slevomat Coding Standard:**
```xml
<config name="installed_paths" value="../../slevomat/coding-standard"/>

<!-- Примеры правил Slevomat -->
<rule ref="SlevomatCodingStandard.TypeHints.DeclareStrictTypes"/>
<rule ref="SlevomatCodingStandard.Classes.ClassStructure"/>
<rule ref="SlevomatCodingStandard.Operators.DisallowEqualOperators"/>
<rule ref="SlevomatCodingStandard.ControlStructures.RequireNullCoalesceOperator"/>
```

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

### 6.1 Список реализованных правил

#### 1. AssertInstanceToStaticCallRector
**Назначение:** Конвертирует PHPUnit assertion вызовы из `$this->assert*()` в `self::assert*()`

**Min PHP Version:** 8.2

**Поддерживаемые методы:**
- `assertInstanceOf`, `assertNotInstanceOf`
- `assertTrue`, `assertFalse`
- `assertSame`, `assertEquals`
- `assertCount`, `assertNull`, `assertNotNull`
- `assertEmpty`, `assertNotEmpty`
- `assertIsString`, `assertIsArray`
- `assertArrayHasKey`, `assertArrayNotHasKey`
- `assertContains`, `assertNotContains`
- `markTestSkipped`
- `assertDatabaseTable` (Laravel)

**Пример:**
```php
// До
class SomeTest extends TestCase
{
    public function test(): void
    {
        $this->assertInstanceOf(Something::class, $object);
        $this->assertTrue($value);
        $this->assertCount(5, $items);
    }
}

// После
class SomeTest extends TestCase
{
    public function test(): void
    {
        self::assertInstanceOf(Something::class, $object);
        self::assertTrue($value);
        self::assertCount(5, $items);
    }
}
```

**Реализация:**
- Наследуется от `AbstractRector`
- Реализует `MinPhpVersionInterface` для контроля версии PHP
- Использует `TestsNodeAnalyzer` для определения тестовых классов
- Работает только внутри классов PHPUnit тестов

---

#### 2. MockObjectStaticToInstanceCallRector
**Назначение:** Конвертирует PHPUnit mock методы из `self::any()` в `$this->any()`

**Min PHP Version:** 8.2

**Поддерживаемые методы:**
- `any()`, `once()`, `never()`
- `atLeast()`, `atLeastOnce()`, `atMost()`
- `exactly()`

**Пример:**
```php
// До
class SomeTest extends TestCase
{
    public function test(): void
    {
        $mock = $this->createMock(Something::class);
        $mock->expects(self::any())
            ->method('someMethod')
            ->willReturn(true);

        $mock->expects(self::once())
            ->method('otherMethod');
    }
}

// После
class SomeTest extends TestCase
{
    public function test(): void
    {
        $mock = $this->createMock(Something::class);
        $mock->expects($this->any())
            ->method('someMethod')
            ->willReturn(true);

        $mock->expects($this->once())
            ->method('otherMethod');
    }
}
```

**Реализация:**
- Наследуется от `AbstractRector`
- Реализует `MinPhpVersionInterface` (PHP 8.2+)
- Использует `TestsNodeAnalyzer` для определения тестовых классов
- Конвертирует `StaticCall` в `MethodCall`

---

### 6.2 Структура кастомных правил

**Базовый шаблон правила:**
```php
<?php

declare(strict_types=1);

namespace Linters\Rector\Rules;

use PhpParser\Node;
use Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Rector\Rector\AbstractRector;
use Rector\ValueObject\PhpVersion;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\Exception\PoorDocumentationException;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @implements MinPhpVersionInterface<Node>
 */
final class CustomRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(
        private readonly TestsNodeAnalyzer $testsNodeAnalyzer,
    ) {
    }

    /**
     * @throws PoorDocumentationException
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rule description',
            [
                new CodeSample(
                    // Before code
                    <<<'CODE_SAMPLE'
// Before
CODE_SAMPLE
                    ,
                    // After code
                    <<<'CODE_SAMPLE'
// After
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [/* Node types to process */];
    }

    /**
     * @param Node $node
     */
    public function refactor(Node $node): ?Node
    {
        // Refactoring logic
        // Return null if no changes, return new Node if changed
    }

    public function provideMinPhpVersion(): int
    {
        return PhpVersion::PHP_82;
    }
}
```

**Ключевые компоненты:**
1. **Namespace:** `Linters\Rector\Rules`
2. **Extends:** `AbstractRector` (базовый класс всех Rector правил)
3. **Implements:** `MinPhpVersionInterface` (опционально, для контроля версии PHP)
4. **getRuleDefinition():** Описание и примеры кода "до/после"
5. **getNodeTypes():** Типы AST узлов для обработки
6. **refactor():** Логика трансформации кода
7. **provideMinPhpVersion():** Минимальная версия PHP (опционально)

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

### 8.1 Скрипты для разработки самой библиотеки

Используются при работе с кодом библиотеки `devwolk/linters`:

```json
{
  "scripts": {
    "rector": [
      "./vendor/bin/rector process --config=./configs/rector.php --clear-cache"
    ],
    "rector-check": [
      "./vendor/bin/rector process --config=./configs/rector.php --clear-cache --dry-run"
    ],
    "php-cs-fixer": [
      "./vendor/bin/php-cs-fixer fix --config=./configs/.php-cs-fixer.dist.php --allow-risky=yes --using-cache=no"
    ],
    "php-cs-fixer-check": [
      "./vendor/bin/php-cs-fixer fix --dry-run --config=./configs/.php-cs-fixer.dist.php --diff -vv --allow-risky=yes --using-cache=no"
    ],
    "phpstan": [
      "./vendor/bin/phpstan src/ConfigGenerator/ analyze --configuration=./configs/phpstan.neon"
    ],
    "phpstan-baseline": [
      "./vendor/bin/phpstan src/ConfigGenerator/ analyze --configuration=./configs/phpstan.neon --generate-baseline"
    ],
    "psalm": [
      "php src/ConfigGenerator/PsalmConfigGenerator.php --target=./psalm.xml",
      "./vendor/bin/psalm --threads=4 --no-cache --config=./psalm.xml",
      "rm ./psalm.xml"
    ],
    "phpcs": [
      "./vendor/bin/phpcs src/ConfigGenerator/ --standard=./configs/phpcs.xml"
    ],
    "phpmd": [
      "./vendor/bin/phpmd src/ConfigGenerator/ src text ./configs/phpmd.ruleset.xml"
    ],
    "composer-unused": [
      "./vendor/bin/composer-unused --configuration=./configs/composer-unused.php"
    ]
  }
}
```

**Особенности:**
- Psalm генератор создаёт временный `psalm.xml` и удаляет его после проверки
- PHP-CS-Fixer использует `--using-cache=no` для чистых запусков
- Rector использует `--clear-cache` для избежания кеширования проблем

---

### 8.2 Скрипты для проекта-потребителя

Используются в проекте, который подключил библиотеку как зависимость:

```json
{
  "scripts": {
    "rector": [
      "./vendor/bin/rector process --config=./vendor/devwolk/linters/configs/rector.php --clear-cache"
    ],
    "rector-check": [
      "./vendor/bin/rector process --config=./vendor/devwolk/linters/configs/rector.php --clear-cache --dry-run"
    ],
    "php-cs-fixer": [
      "./vendor/bin/php-cs-fixer fix --config=./vendor/devwolk/linters/configs/.php-cs-fixer.dist.php --allow-risky=yes --using-cache=no"
    ],
    "php-cs-fixer-check": [
      "./vendor/bin/php-cs-fixer fix --dry-run --config=./vendor/devwolk/linters/configs/.php-cs-fixer.dist.php --diff -vv --allow-risky=yes --using-cache=no"
    ],
    "phpstan": [
      "./vendor/bin/phpstan analyze --configuration=./vendor/devwolk/linters/configs/phpstan.neon"
    ],
    "phpstan-baseline": [
      "./vendor/bin/phpstan analyze --configuration=./vendor/devwolk/linters/configs/phpstan.neon --generate-baseline"
    ],
    "psalm": [
      "php ./vendor/devwolk/linters/src/ConfigGenerator/.php --target=./psalm.xml",
      "./vendor/bin/psalm --threads=4 --no-cache --config=./psalm.xml",
      "rm ./psalm.xml"
    ],
    "phpcs": [
      "./vendor/bin/phpcs --standard=./vendor/devwolk/linters/configs/phpcs.xml"
    ],
    "phpmd": [
      "./vendor/bin/phpmd app,config,tests text ./vendor/devwolk/linters/configs/phpmd.ruleset.xml"
    ]
  }
}
```

**Ключевые отличия:**
- Все пути к конфигам указывают на `./vendor/devwolk/linters/configs/`
- Psalm генератор запускается из vendor-директории библиотеки
- ConfigurationLoader автоматически читает `extra.linters` из корневого `composer.json` проекта
- Пути к исходникам берутся из `extra.linters.<tool>.paths`

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

1. Устанавливается одной командой `composer require --dev devwolk/linters`
2. Настраивается через `composer.json > extra.linters` с минимальными усилиями
3. Предоставляет единые конфигурации для 7 инструментов статического анализа
4. Содержит библиотеку готовых кастомных Rector rules
5. Имеет исчерпывающую документацию для всех сценариев
6. Легко интегрируется в CI/CD пайплайны
7. Работает из коробки с Laravel, Symfony
8. Позволяет расширение и переопределение настроек
9. Покрыта тестами и готова к maintenance
10. Опубликована на Packagist и готова к использованию