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
      "rector": {
        "paths": [
          "/src"
        ],
        "skip": [
          "/vendor"
        ],
        "cache_dir": "./var/rector-cache"
      },
      "php-cs-fixer": {
        "paths": [
          "/src"
        ],
        "skip": [],
        "parallel": true
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
      },
      "composer-unused": {
        "named-filters": []
      }
    }
  }
}
```

## Текущее состояние (snapshot)

- поддержка: Rector, PHP-CS-Fixer, PHPStan, PHPCS, PHPMD, composer-unused;
- генерация и запуск: PHPStan/PHPCS/PHPMD через `linters generate`/`linters run`;
- шаблоны: `configs/phpstan.neon`, `configs/phpcs.xml`, `configs/phpmd.ruleset.xml`;
- динамические конфиги: `configs/rector.php`, `configs/.php-cs-fixer.dist.php`, `configs/composer-unused.php`;
- документация и примеры обновлены под текущие опции;
- остаются: прогон тестов и валидация CLI/шаблонов.

---

## Оставшиеся задачи

### Phase 3: Тесты (рекомендуется)

- [x] Добавить тесты генераторов (phpstan/phpcs/phpmd)
- [x] Добавить тесты для ToolRunner
- [x] Добавить тесты кастомных Rector правил

### Phase 4: Улучшения (опционально)

- [ ] Унифицировать CLI через Tool enum (добавить rector/php-cs-fixer/composer-unused)
- [ ] Финальный аудит отсутствия hardcoded путей/дефолтов

---

## Чеклист завершения

- [x] документация соответствует коду
- [x] примеры функциональны и согласованы по версии
- [ ] тесты проходят
- [ ] все `make` цели работают
- [ ] нет hardcoded путей/дефолтов в шаблонах и генераторах
