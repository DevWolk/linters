# Полный анализ библиотеки devwolk/linters (обновлено)

## 0. Цель и стандарт

Целевая архитектура пакета:

- единый CLI стандарт: `./vendor/bin/linters generate <tool>` и `./vendor/bin/linters run <tool>`;
- шаблоны для всех проверок в `configs/`;
- никаких project-specific путей/дефолтов внутри пакета;
- все настройки берутся из `extra.linters` в `composer.json`;
- гибкость под Laravel/Symfony через `rector.frameworks` и `extra.linters.<tool>.config`.

## 1. Текущее состояние

Что уже есть:

- поддержка Rector, PHP-CS-Fixer, PHPStan, PHPCS, PHPMD, composer-unused;
- генерация и запуск через `linters generate`/`linters run` для PHPStan/PHPCS/PHPMD;
- шаблоны: `configs/phpstan.neon`, `configs/phpcs.xml`, `configs/phpmd.ruleset.xml`;
- динамические конфиги: `configs/rector.php`, `configs/.php-cs-fixer.dist.php`, `configs/composer-unused.php`;
- документация и примеры синхронизированы с текущими опциями;
- добавлены тесты генераторов, ToolRunner и кастомных Rector правил.

## 2. Архитектура (кратко)

- `ConfigurationLoader` читает `extra.linters` и преобразует пути (ожидаются пути с `/`).
- `ConfigGenerator` для PHPStan/PHPCS/PHPMD строят конфиги из шаблонов.
- CLI на Symfony Console: `generate`, `run`.

## 3. Оставшиеся пробелы

- требуется финальный аудит отсутствия hardcoded путей/дефолтов;
- опционально: унифицировать CLI через Tool enum (добавить rector/php-cs-fixer/composer-unused);
- нужен прогон тестов и `make` целей.

## 4. Следующие шаги

- прогнать `make` цели и тесты;
- выполнить финальный аудит на отсутствие хардкода;
- отдельно решить, нужна ли унификация CLI через Tool enum.
