# Installation Guide

This guide will help you install and configure the `devwolk/linters` package in your PHP project.

## Requirements

Before installing, ensure your project meets these requirements:

| Requirement | Version |
|------------|---------|
| PHP | ^8.2 |
| Composer | ^2.0 |
| Operating System | Unix/Linux/macOS |

## Step 1: Install via Composer

Add the package to your project as a development dependency:

```bash
composer require --dev devwolk/linters
```

This will install the package and all required linting tools.

## Step 2: Configure composer.json

Add the `extra.linters` configuration to your `composer.json`:

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/app", "/src", "/tests"],
        "skip": ["/vendor", "/storage"]
      },
      "php-cs-fixer": {
        "paths": ["/app", "/src"],
        "skip": ["*.blade.php"]
      },
      "phpstan": {
        "paths": ["/app", "/src"],
        "skip": ["/vendor"],
        "level": 8
      },
      "psalm": {
        "paths": ["/app", "/src"],
        "skip": ["/vendor"],
        "config": {}
      },
      "phpcs": {
        "paths": ["/app", "/src"],
        "skip": []
      },
      "phpmd": {
        "paths": ["/app", "/src"],
        "skip": []
      }
    }
  }
}
```

### Configuration Explanation

- **paths**: Directories to analyze (relative to project root)
- **skip**: Directories or patterns to exclude from analysis
- **level**: (PHPStan only) Analysis strictness level (0-9 or 'max')
- **config**: (Psalm only) Additional XML configuration options

## Step 3: Add Composer Scripts

Add convenient scripts to your `composer.json`:

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
      "./vendor/bin/php-cs-fixer fix --config=./vendor/devwolk/linters/configs/.php-cs-fixer.dist.php --allow-risky=yes"
    ],
    "php-cs-fixer-check": [
      "./vendor/bin/php-cs-fixer fix --dry-run --config=./vendor/devwolk/linters/configs/.php-cs-fixer.dist.php --diff -vv"
    ],
    "phpstan": [
      "./vendor/bin/phpstan analyze --configuration=./vendor/devwolk/linters/configs/phpstan.neon"
    ],
    "phpstan-baseline": [
      "./vendor/bin/phpstan analyze --configuration=./vendor/devwolk/linters/configs/phpstan.neon --generate-baseline"
    ],
    "psalm": [
      "php ./vendor/devwolk/linters/src/psalm_config.php --target=./psalm.xml",
      "./vendor/bin/psalm --threads=4 --no-cache --config=./psalm.xml",
      "rm ./psalm.xml"
    ],
    "phpcs": [
      "./vendor/bin/phpcs --standard=./vendor/devwolk/linters/configs/phpcs.xml"
    ],
    "phpmd": [
      "./vendor/bin/phpmd app,src text ./vendor/devwolk/linters/configs/phpmd.ruleset.xml"
    ]
  }
}
```

## Step 4: First Run

### Run Individual Tools

Test each tool to ensure it's configured correctly:

```bash
# Check code style (dry-run)
composer rector-check

# Fix code style
composer rector

# Check PHP-CS-Fixer (dry-run)
composer php-cs-fixer-check

# Fix with PHP-CS-Fixer
composer php-cs-fixer

# Run PHPStan
composer phpstan

# Run Psalm
composer psalm

# Run PHP_CodeSniffer
composer phpcs

# Run PHPMD
composer phpmd
```

### Run All Tools in Sequence

For a complete code quality check:

```bash
composer rector-check && \
composer php-cs-fixer-check && \
composer phpstan && \
composer psalm && \
composer phpcs
```

## Step 5: Verify Installation

Confirm everything is working:

```bash
# Check if Rector can analyze your code
composer rector-check

# Check if PHPStan runs without errors
composer phpstan

# Verify PHP-CS-Fixer configuration
composer php-cs-fixer-check
```

If you see analysis results without configuration errors, the installation is successful!

## Common Issues

### Issue: "composer.json not found"

**Solution**: Ensure you're running commands from your project root directory where `composer.json` is located.

### Issue: "Memory limit exceeded"

**Solution**: Increase PHP memory limit:

```bash
php -d memory_limit=2G vendor/bin/phpstan analyze
```

Or add to `php.ini`:
```ini
memory_limit = 2G
```

### Issue: "Too many errors reported"

**Solution**:
1. For PHPStan, generate a baseline:
   ```bash
   composer phpstan-baseline
   ```

2. For Rector, start with specific paths:
   ```json
   {
     "extra": {
       "linters": {
         "rector": {
           "paths": ["/src"]
         }
       }
     }
   }
   ```

## Next Steps

- Read [CONFIGURATION.md](./CONFIGURATION.md) for detailed configuration options
- Check [RECTOR_GUIDE.md](./RECTOR_GUIDE.md) for Rector usage
- See [PHPSTAN_GUIDE.md](./PHPSTAN_GUIDE.md) for PHPStan best practices
- Review [CUSTOM_RECTOR_RULES.md](./CUSTOM_RECTOR_RULES.md) for available custom rules

## Integration with CI/CD

Add to your `.github/workflows/ci.yml` (GitHub Actions):

```yaml
name: Code Quality

on: [push, pull_request]

jobs:
  quality:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install

      - name: Run Rector (dry-run)
        run: composer rector-check

      - name: Run PHPStan
        run: composer phpstan

      - name: Run PHP-CS-Fixer (dry-run)
        run: composer php-cs-fixer-check
```
