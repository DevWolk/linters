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
│   │   ├── laravel.php                → Laravel sets (если rector-laravel установлен)
│   │   └── symfony.php                → ПУСТОЙ (не реализован)
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
Tool::PHP_STAN.requiresGeneration() = true
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
| rector          | REQ   | OPT       | OPT        | OPT*     | OPT       | -        | OPT        | -             |
| php-cs-fixer    | REQ   | OPT       | OPT        | OPT      | OPT       | -        | -          | -             |
| phpstan         | REQ   | OPT       | OPT        | OPT      | OPT       | OPT      | -          | -             |
| phpcs           | REQ   | OPT       | OPT        | OPT      | OPT       | -        | -          | -             |
| phpmd           | REQ   | OPT       | OPT        | -        | -         | OPT      | -          | -             |
| composer-unused | -     | -         | -          | -        | -         | -        | -          | OPT           |

REQ = required, OPT = optional, - = not supported
*rector parallel по умолчанию enabled

---

## Критические баги

### 1. Баг в ConfigValidation::stringList() - unset не работает

**Файл:** `src/Utils/ConfigValidation.php:18-20`

**Проблема:** `unset($item)` не удаляет элемент из массива. Это локальная переменная цикла, её удаление не влияет на исходный массив.

```php
foreach ($list as $item) {
    if (!is_string($item)) {
        unset($item);  // БАГ: это ничего не делает!
    }
}
return $list;  // Возвращает исходный массив с не-строковыми элементами
```

**Решение:** Использовать `array_filter`:

```php
public static function stringList(string|array $value): array
{
    $list = (array) $value;

    return array_filter($list, static fn($item): bool => is_string($item));
}
```

---

### 2. ConfigurationLoader::getToolConfig() - ошибка при отсутствии конфигурации инструмента

**Файл:** `src/Utils/ConfigurationLoader.php:105-108`

**Проблема:** Если инструмент не сконфигурирован в composer.json, метод выбросит `Undefined array key` warning:

```php
private function getToolConfig(string $tool): array
{
    return $this->config[$tool];  // Warning если ключ не существует
}
```

**Пример:** Если в composer.json определён только `rector`, а пользователь вызывает `linters run phpstan`, будет ошибка.

**Решение:** Добавить проверку существования ключа:

```php
private function getToolConfig(string $tool): array
{
    if (!array_key_exists($tool, $this->config)) {
        throw new RuntimeException(
            "Missing configuration: extra.linters.{$tool}. "
            . "Add this section to your composer.json."
        );
    }

    return $this->config[$tool];
}
```

---

## Незавершённые функции

### 3. Symfony framework preset не реализован

**Файл:** `src/Rector/Configs/Sets/symfony.php`

**Проблема:** Файл содержит только `return;` и ничего не делает:

```php
return static function (RectorConfig $rectorConfig): void {
    return;  // Пустая реализация
};
```

Однако `symfony` указан как поддерживаемый фреймворк в `RectorConfig::isSymfonyProject()`.

**Решение (выбрать одно):**

A. Реализовать Symfony sets аналогично Laravel:
```php
return static function (RectorConfig $rectorConfig): void {
    if (!class_exists(\Rector\Symfony\Set\SymfonySetList::class)) {
        return;
    }

    $rectorConfig->sets([
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_64,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_CODE_QUALITY,
        // ...
    ]);
};
```

B. Удалить `symfony` из списка поддерживаемых фреймворков и обновить документацию.

C. Добавить предупреждение при использовании:
```php
return static function (RectorConfig $rectorConfig): void {
    trigger_error('Symfony framework preset is not yet implemented', E_USER_NOTICE);
};
```

**Рекомендация:** Вариант A - реализовать полноценную поддержку.

---

## Документация и примеры

### 4. Обновить документацию в соответствии с актуальной схемой

**Файлы:**
- `docs/CONFIGURATION.md` - в основном актуален
- `docs/INSTALLATION.md` - в основном актуален
- `docs/PHPSTAN_GUIDE.md` - проверить на устаревшие ключи
- `docs/RECTOR_GUIDE.md` - проверить на устаревшие ключи
- `docs/TROUBLESHOOTING.md` - проверить на устаревшие ключи
- `docs/CUSTOM_RECTOR_RULES.md` - проверить актуальность

**План:**
1. Убедиться что везде используются только поддерживаемые ключи
2. Удалить упоминания устаревших ключей (`skip`, `level`, `target`, `format`, `rulesets`, `config`, `template`)
3. Уточнить что paths используются as-is (без нормализации)
4. Обновить таблицу поддерживаемых ключей по инструментам

---

### 5. Проверить примеры в examples/

**Файлы:**
- `examples/laravel/composer.json` - выглядит корректно
- `examples/laravel/README.md` - проверить
- `examples/symfony/composer.json` - выглядит корректно (но symfony preset не работает!)
- `examples/symfony/README.md` - проверить

**План:**
1. Проверить README файлы в примерах
2. Добавить предупреждение в symfony example что preset не полностью реализован (или реализовать preset)

---

## Улучшения качества кода

### 6. Валидация ключей внутри конфигурации инструмента

**Статус:** НЕ ТРЕБУЕТСЯ

**Обоснование:** Согласно задаче, неизвестные ключи должны игнорироваться. Это предотвращает ошибки при опечатках, но не нарушает ограничение "только 7 разрешённых точек конфигурации" - пользователи всё равно не могут изменить правила линтеров.

---

### 7. Тесты для кастомных Rector rules

**Файлы:** Отсутствуют тестовые фикстуры для `MockObjectStaticToInstanceCallRector` и `AssertInstanceToStaticCallRector`

**План:**
1. Создать папку `tests/Unit/Rector/Rules/`
2. Добавить тесты в формате Rector:
```php
// Fixture/mock_static_to_instance.php.inc
<?php
// Before
$mock->expects(self::any())->method('foo');
-----
// After
$mock->expects($this->any())->method('foo');
?>
```

---

## Приоритеты

1. **КРИТИЧНО:** Исправить баг в `ConfigValidation::stringList()` (#1)
2. **КРИТИЧНО:** Добавить проверку существования конфигурации инструмента (#2)
3. **ВАЖНО:** Реализовать Symfony framework preset или обновить документацию (#3)
4. **ЖЕЛАТЕЛЬНО:** Обновить документацию (#4, #5)
5. **ЖЕЛАТЕЛЬНО:** Добавить тесты для Rector rules (#7)

---

## Соответствие первоначальной задаче

Проект **в целом соответствует** требованиям:

**Разрешённая конфигурация (только 7 точек):**
- [x] Директории и файлы для включения (paths)
- [x] Директории и файлы для исключения (skip_dirs, skip_files)
- [x] Фреймворк для пресетов (rector.frameworks)
- [x] Baseline файл (phpstan.baseline, phpmd.baseline)
- [x] Cache directory (cache_dir)
- [x] Параллельность (parallel)
- [x] Named filters для composer-unused (named-filters)

**Правила линтеров НЕ конфигурируются пользователями:**
- [x] Все правила зашиты в configs/
- [x] Кастомные Rector rules встроены в библиотеку
- [x] Неизвестные ключи игнорируются (не применяются)
