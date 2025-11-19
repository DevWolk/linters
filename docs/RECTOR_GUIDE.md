# Rector Usage Guide

Complete guide to using Rector for automated code refactoring and upgrades.

## Table of Contents

- [What is Rector?](#what-is-rector)
- [Basic Usage](#basic-usage)
- [PHP Version Migrations](#php-version-migrations)
- [Framework Migrations](#framework-migrations)
- [Custom Rules](#custom-rules)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

## What is Rector?

Rector is a PHP tool for instant code upgrades and refactoring. It can:

- Upgrade your code to newer PHP versions
- Migrate framework versions (Laravel, Symfony, etc.)
- Apply code quality improvements
- Enforce coding standards
- Perform automated refactoring

## Basic Usage

### Check What Would Change (Dry Run)

Always run in dry-run mode first to see what changes would be made:

```bash
composer rector-check
```

This will show you all proposed changes without modifying files.

### Apply Changes

Once you've reviewed the changes, apply them:

```bash
composer rector
```

### Process Specific Files or Directories

```bash
./vendor/bin/rector process app/Services --config=./vendor/devwolk/linters/configs/rector.php --dry-run
```

### Clear Cache

If you encounter issues, clear the Rector cache:

```bash
./vendor/bin/rector process --config=./vendor/devwolk/linters/configs/rector.php --clear-cache
```

## PHP Version Migrations

### Available PHP Migrations

The library includes migrations up to PHP 8.3:

```bash
# PHP 8.0 features
- Union types
- Named arguments
- Attributes
- Match expressions
- Nullsafe operator

# PHP 8.1 features
- Enums
- Readonly properties
- First-class callable syntax
- New in initializers

# PHP 8.2 features
- Readonly classes
- DNF types
- True/false/null standalone types

# PHP 8.3 features
- Typed class constants
- Dynamic class constant fetch
```

### Migration Process

1. **Backup your code** (use git)

2. **Run dry-run** to see changes:
   ```bash
   composer rector-check
   ```

3. **Review changes** carefully

4. **Apply changes**:
   ```bash
   composer rector
   ```

5. **Test your application** thoroughly

6. **Commit changes**:
   ```bash
   git add .
   git commit -m "feat: upgrade to PHP 8.3"
   ```

### Gradual Migration

For large projects, migrate in stages:

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/app/Services"],
        "skip": []
      }
    }
  }
}
```

Then gradually add more paths.

## Framework Migrations

### Laravel Migrations

The library includes Laravel migrations up to version 11.0:

```bash
composer rector-check
```

Changes include:
- Route model binding updates
- Helper function updates
- Middleware changes
- Event/Listener updates
- Database facade changes

### Symfony Migrations

Includes Symfony migrations for modern versions:
- Service definitions
- Event dispatcher updates
- Form component changes
- Console command updates

## Custom Rules

This library includes custom Rector rules. See [CUSTOM_RECTOR_RULES.md](./CUSTOM_RECTOR_RULES.md) for details.

### Using Custom Rules

Custom rules are automatically applied when you run:

```bash
composer rector
```

To disable custom rules, skip them in configuration.

## Best Practices

### 1. Use Version Control

Always use git before running Rector:

```bash
git add .
git commit -m "chore: checkpoint before rector"
composer rector
```

### 2. Run in Stages

For large codebases:

1. Run on small directory first
2. Test thoroughly
3. Gradually expand scope

### 3. Review Every Change

Never blindly apply Rector changes:

```bash
composer rector-check > rector-changes.txt
# Review rector-changes.txt
composer rector
```

### 4. Test After Changes

Always run tests after Rector:

```bash
composer rector
composer test
composer phpstan
```

### 5. Combine with Other Tools

Use Rector with other tools for best results:

```bash
composer rector && \
composer php-cs-fixer && \
composer phpstan
```

## Common Use Cases

### Update Deprecated Methods

Rector automatically updates deprecated methods:

```php
// Before
$user = User::find($id);
if ($user) {
    // ...
}

// After (with null-safe operator)
$user?->getName();
```

### Convert Array to Modern Syntax

```php
// Before
$data = array('key' => 'value');

// After
$data = ['key' => 'value'];
```

### Apply Type Declarations

```php
// Before
public function getName()
{
    return $this->name;
}

// After
public function getName(): string
{
    return $this->name;
}
```

### Convert to Constructor Property Promotion

```php
// Before
public function __construct(
    private string $name,
    private int $age
) {
    $this->name = $name;
    $this->age = $age;
}

// After (PHP 8.0+)
public function __construct(
    private string $name,
    private int $age
) {
}
```

## Performance Tips

### 1. Use Parallel Processing

Rector runs in parallel by default (configured in `rector.php`):

```php
->withParallel(360, 2, 40)
```

### 2. Limit Scope

Process only what's necessary:

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/app/Http/Controllers"],
        "skip": ["/app/Legacy"]
      }
    }
  }
}
```

### 3. Use Cache

Cache is enabled by default:

```php
->withCache(
    cacheDirectory: '/tmp/rector',
    cacheClass: FileCacheStorage::class
)
```

## Troubleshooting

### Issue: "Out of memory"

**Solution**:
```bash
php -d memory_limit=2G vendor/bin/rector process
```

### Issue: "Changes not being applied"

**Solutions**:
1. Clear cache:
   ```bash
   composer rector -- --clear-cache
   ```

2. Check file permissions

3. Verify configuration is loaded

### Issue: "Too many changes"

**Solution**: Process incrementally:

```bash
./vendor/bin/rector process app/Models --config=... --dry-run
./vendor/bin/rector process app/Services --config=... --dry-run
```

### Issue: "Infinite loop detected"

**Solution**: Some rules conflict. Skip problematic rules in your config.

## Advanced Features

### Debugging

Enable debug mode to see what Rector is doing:

```bash
./vendor/bin/rector process --config=... --dry-run --debug
```

### Generate Config

Generate a custom Rector config:

```bash
./vendor/bin/rector init
```

### List Rules

See all available rules:

```bash
./vendor/bin/rector list
```

## Integration with CI/CD

### GitHub Actions

```yaml
name: Rector

on: [push, pull_request]

jobs:
  rector:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install

      - name: Run Rector
        run: composer rector-check
```

### GitLab CI

```yaml
rector:
  image: php:8.2
  script:
    - composer install
    - composer rector-check
```

## Learn More

- [Rector Documentation](https://getrector.com/documentation)
- [Custom Rector Rules](./CUSTOM_RECTOR_RULES.md)
- [Troubleshooting](./TROUBLESHOOTING.md)
