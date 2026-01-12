# devwolk/linters

Centralized PHP linter configurations and static analysis tools for PHP projects.

## Target Standard (Spec)

- Single CLI entrypoint: `./vendor/bin/linters generate <tool>` and `./vendor/bin/linters run <tool>`
- Templates for every check live under `configs/`
- No project-specific paths or defaults inside the package
- All settings come from `extra.linters` in the consuming `composer.json`
- Framework flexibility via `rector.frameworks` and tool-specific `*.config`

## Current Status (Snapshot)

- Implemented: Rector, PHP-CS-Fixer, PHPStan, PHPCS, PHPMD, composer-unused
- CLI: `linters run` supports all tools; `linters generate` is for PHPStan/PHPCS/PHPMD
- Templates: `configs/phpstan.neon`, `configs/phpcs.xml`, `configs/phpmd.ruleset.xml`,
  plus dynamic configs for Rector, PHP-CS-Fixer, and composer-unused
- Pending: run test/make targets

## Supported Tools

| Tool                                                                  | Version | Status |
|-----------------------------------------------------------------------|---------|--------|
| [Rector](https://getrector.com/)                                      | ^2.2    | Working (dynamic config) |
| [PHP-CS-Fixer](https://cs.symfony.com/)                               | ^3.89   | Working (dynamic config) |
| [PHPStan](https://phpstan.org/)                                       | ^2.1    | Working (generated config, not verified) |
| [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)       | ^4.0    | Working (generated config, not verified) |
| [PHPMD](https://phpmd.org/)                                           | ^2.15   | Working (generated config, not verified) |
| [composer-unused](https://github.com/composer-unused/composer-unused) | ^0.9    | Working |

## Configuration (Target Schema)

All tool settings live in `extra.linters`:

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/src"],
        "skip": ["/vendor"],
        "frameworks": "laravel",
        "cache_dir": "./var/rector-cache",
        "parallel": true
      },
      "php-cs-fixer": {
        "paths": ["/src"],
        "skip_dirs": [],
        "skip_files": [],
        "parallel": {
          "enabled": true,
          "max_processes": 4
        }
      },
      "phpstan": {
        "paths": ["/src"],
        "skip": ["/vendor"]
      },
      "phpcs": {
        "paths": ["/src"],
        "skip": []
      },
      "phpmd": {
        "paths": ["/src"],
        "skip": ["/vendor"]
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

All tool configs live under `extra.linters.<tool>`.
## CLI (Single Standard)

```bash
./vendor/bin/linters run rector
./vendor/bin/linters run php-cs-fixer
./vendor/bin/linters generate phpstan
./vendor/bin/linters generate phpcs
./vendor/bin/linters generate phpmd
./vendor/bin/linters run phpstan
./vendor/bin/linters run phpcs
./vendor/bin/linters run phpmd
./vendor/bin/linters run composer-unused
```

`linters run <tool>` regenerates configs for phpstan/phpcs/phpmd on each run.
Generated configs are written to `phpstan.neon`, `phpcs.xml`, and `phpmd.ruleset.xml` in the project root.
`linters generate` is only available for `phpstan`, `phpcs`, and `phpmd`.

## Framework Support

Framework-specific Rector rules are controlled by `rector.frameworks`:

```json
{
  "extra": {
    "linters": {
      "rector": {
        "frameworks": ["laravel", "symfony"]
      }
    }
  }
}
```

Framework presets are limited to Rector via `rector.frameworks`.

## Templates

`configs/` provides the shared templates used by generators:

- `configs/phpstan.neon`
- `configs/phpcs.xml`
- `configs/phpmd.ruleset.xml`
- `configs/rector.php`
- `configs/.php-cs-fixer.dist.php`
- `configs/composer-unused.php`
