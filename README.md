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
- Generators and runners: PHPStan/PHPCS/PHPMD via `linters generate` and `linters run`
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
        "cache_dir": "./var/rector-cache"
      },
      "php-cs-fixer": {
        "paths": ["/src"],
        "skip_dirs": [],
        "skip_files": [],
        "parallel": true
      },
      "phpstan": {
        "paths": ["/src"],
        "skip": ["/vendor"],
        "target": "./phpstan.neon"
      },
      "phpcs": {
        "paths": ["/src"],
        "skip": [],
        "target": "./phpcs.xml"
      },
      "phpmd": {
        "paths": ["/src"],
        "skip": ["/vendor"],
        "target": "./phpmd.ruleset.xml",
        "format": "text"
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
See `TODO.md` for the active plan and checks.

## CLI (Single Standard)

```bash
./vendor/bin/linters generate phpstan
./vendor/bin/linters generate phpcs
./vendor/bin/linters generate phpmd
./vendor/bin/linters run phpstan
./vendor/bin/linters run phpcs
./vendor/bin/linters run phpmd
```

`linters run <tool>` regenerates the config on each run using `extra.linters.<tool>`.
The `--target` and `--config` options override `extra.linters.<tool>.target` and
`extra.linters.<tool>.template`; `--format` overrides `extra.linters.phpmd.format`.

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

For PHPStan, use `extra.linters.phpstan.config` to merge additional settings.
For PHPMD, `phpmd.rulesets` filters rulesets inside the template and adds missing ones;
use `phpmd.template` (or `--config`) when you need full control.

## Templates

`configs/` provides the shared templates used by generators:

- `configs/phpstan.neon`
- `configs/phpcs.xml`
- `configs/phpmd.ruleset.xml`
- `configs/rector.php`
- `configs/.php-cs-fixer.dist.php`
- `configs/composer-unused.php`

## Roadmap

See `TODO.md` for the active plan and gap list.
