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
- динамические конфиги: `configs/rector.php`, `configs/.php-cs-fixer.dist.php`;
- дефолтных `extra.linters` в пакете больше нет;
- framework переключение для Rector реализовано (`rector.frameworks`).

Что отсутствует:

- тесты генераторов и подтверждение запуска команд;
- финальная проверка отсутствия hardcoded путей/дефолтов.

## 2. Архитектура (кратко)

- `ConfigurationLoader` читает `extra.linters` и преобразует пути (ожидаются пути с `/`).
- `ConfigGenerator` для PHPStan/PHPCS/PHPMD строят конфиги из шаблонов.
- CLI на Symfony Console: `generate`, `run`.

## 3. Несоответствия целевому стандарту

1. Требуется финальная проверка "без хардкода" и отсутствие project-specific путей.
2. Отсутствуют тесты генераторов и проверки команд (`composer`/`make`).

## 4. Приоритеты

- Стандартизация: закрепить единый CLI и отсутствие hardcoded путей.
- Гибкость под Laravel/Symfony: описать настройки через `extra.linters.*.config`.
- Валидация: прогон `composer`/`make`, тесты генераторов, улучшение `ConfigurationLoader`.

## 5. Итог

База уже собрана: единый CLI, генераторы и шаблоны для PHPStan/PHPCS/PHPMD, динамические конфиги для Rector/PHP-CS-Fixer. До целевого стандарта не хватает финальной проверки на отсутствие hardcoded путей и валидации команд/тестов.
