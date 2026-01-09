# TODO Plan: devwolk/linters

## Цель (target standard)

- единый стандарт команд: `./vendor/bin/linters generate <tool>` и `./vendor/bin/linters run <tool>`;
- шаблоны для всех проверок в `configs/`;
- никаких project-specific путей/дефолтов в пакете;
- вся конфигурация через `extra.linters` в `composer.json`;
- гибкость под Laravel/Symfony через `rector.frameworks` и `extra.linters.<tool>.config`.

Пример целевой конфигурации (минимальный фрагмент):

```json
{

  "extra": {
    "linters": {
      "php-cs-fixer": {
        "paths": [
          "/src"
        ],
        "skip": []
      },
      "phpstan": {
        "paths": [
          "/src"
        ],
        "skip": [
          "/vendor"
        ],
        "target": "./phpstan.neon"
      },
      "phpmd": {
        "paths": [
          "/src"
        ],
        "skip": [
          "/vendor"
        ],
        "target": "./phpmd.ruleset.xml",
        "format": "text"
      }
    }
  }
}
```

Другие инструменты (`rector`, `phpcs`, `composer-unused`) используют тот же формат ключей
и управляются через `extra.linters.<tool>`.

## Текущее состояние (snapshot)

- поддержка: Rector, PHP-CS-Fixer, PHPStan, PHPCS, PHPMD, composer-unused;
- генерация и запуск: PHPStan/PHPCS/PHPMD через `linters generate`/`linters run`;
- шаблоны: `configs/phpstan.neon`, `configs/phpcs.xml`, `configs/phpmd.ruleset.xml`;
- ключ для fixer: `php-cs-fixer`.

---

## Phase 1: Стандартизация (breaking)

1. Закрепить единый CLI:
   - все scripts -> `vendor/bin/linters`
   - использовать `linters run <tool>` вместо отдельных команд
2. Проверить отсутствие hardcoded путей и дефолтов:
   - пакету запрещено навязывать `/src`, `/vendor` и др.
3. Убедиться, что везде используется ключ `php-cs-fixer` (без `cs-fixer`).

## Phase 2: Шаблоны и фреймворки

1. Проверить, что шаблоны есть для всех поддерживаемых инструментов.
2. Laravel/Symfony:
   - `rector.frameworks` (уже есть)
   - описать `extra.linters.phpstan.config` и `extra.linters.phpmd.rulesets` как способ
     добавлять фреймворк-специфичные настройки без хардкода.
3. Обновить примеры для Laravel/Symfony под целевой стандарт.

## Phase 3: Проверки и тесты

1. Проверить `composer phpstan/phpcs/phpmd`.
2. Проверить `make` цели.
3. Добавить тесты генераторов (phpstan/phpcs/phpmd).
4. Улучшить `ConfigurationLoader`:
   - валидация структуры
   - нормализация путей
   - разрешить class names в `rector.skip` (если нужно)
5. Аудит на отсутствие project-specific путей в `configs/` и скриптах.

---

## Чеклист завершения

- [ ] `composer rector` работает с динамическими путями
- [ ] `composer php-cs-fixer` работает с ключом `php-cs-fixer`
- [ ] `composer phpstan` работает с динамическими путями
- [ ] `composer phpcs` работает с динамическими путями
- [ ] `composer phpmd` работает с динамическими путями
- [ ] все `make` цели работают
- [ ] документация соответствует коду
- [ ] примеры функциональны
- [ ] тесты проходят
- [ ] нет hardcoded путей/дефолтов в шаблонах и генераторах
