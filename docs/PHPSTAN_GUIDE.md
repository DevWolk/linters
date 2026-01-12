# PHPStan Usage Guide

Complete guide to using PHPStan for static analysis.

## What is PHPStan?

PHPStan is a static analysis tool that finds bugs in your code without running it. It checks:
- Type correctness
- Method existence
- Property access
- Return type compatibility
- And much more

## Basic Usage

### Analyze Your Code

```bash
composer phpstan
```

### Generate Baseline

For existing projects with many errors:

```bash
composer phpstan-baseline
```

This creates `phpstan-baseline.neon` with all current errors, allowing you to fix them gradually.
Add `extra.linters.phpstan.baseline` to include it in the generated config.

## Analysis Levels

PHPStan has 10 levels (0-9):

| Level | Description | Best For |
|-------|-------------|----------|
| 0 | Basic checks | Legacy code |
| 1-3 | Gradually stricter | Old projects |
| 4-6 | Production ready | Most projects |
| 7-8 | Very strict | New projects |
| 9/max | Maximum strictness | Strict projects |

**Recommended**: Start at level 6, aim for level 8.
The package template sets the level to 8 and it is not configurable via `extra.linters`.

## Configuration

Configure in `composer.json`:

```json
{
  "extra": {
    "linters": {
      "phpstan": {
        "paths": ["app", "src"],
        "skip_dirs": ["vendor"],
        "skip_files": [],
        "baseline": "phpstan-baseline.neon",
        "cache_dir": ".cache/phpstan",
        "parallel": true
      }
    }
  }
}
```

The `phpstan.neon` file is generated from `extra.linters.phpstan` on each run; avoid editing it manually.
Ensure `paths` is set in `extra.linters.phpstan` (it's required).

## Common Errors and Solutions

### "Method ... not found"

**Error:**
```
Method App\User::getName() not found
```

**Solutions:**
1. Add the method
2. Use PHPDoc:
```php
/**
 * @method string getName()
 */
class User {}
```

### "Property ... does not exist"

**Error:**
```
Access to undefined property App\User::$name
```

**Solutions:**
1. Declare the property:
```php
class User {
    private string $name;
}
```

2. Use PHPDoc:
```php
/**
 * @property string $name
 */
class User {}
```

### "Return type missing"

**Error:**
```
Method App\User::getName() has no return type specified
```

**Solution:**
```php
public function getName(): string
{
    return $this->name;
}
```

### "Possibly undefined variable"

**Error:**
```
Variable $user might not be defined
```

**Solution:**
```php
// Before
if ($condition) {
    $user = User::find(1);
}
return $user; // Error

// After
$user = null;
if ($condition) {
    $user = User::find(1);
}
return $user; // OK (but still check for null)
```

## Advanced Features

### Ignoring Errors

In code:
```php
/** @phpstan-ignore-next-line */
$result = $this->legacyMethod();
```

In configuration:
```neon
parameters:
    ignoreErrors:
        - '#Call to deprecated method#'
```

### Custom Rules

PHPStan supports custom rules. See documentation for details.

### Bleeding Edge

Enable experimental features:
```neon
includes:
    - vendor/phpstan/phpstan/conf/bleedingEdge.neon
```

## Laravel Projects

Install Larastan:

```bash
composer require --dev nunomaduro/larastan
```

It provides:
- Eloquent model analysis
- Route checking
- Facade support
- Helper function types

## Symfony Projects

PHPStan has excellent Symfony support with extensions for:
- Service container
- Form types
- Console commands
- Doctrine entities

## Performance Tips

### 1. Limit Analysis Scope

```json
{
  "extra": {
    "linters": {
      "phpstan": {
        "paths": ["/app/Services"]
      }
    }
  }
}
```

### 2. Use Result Cache

PHPStan caches results automatically. Clear when needed:

```bash
./vendor/bin/phpstan clear-result-cache
```

### 3. Parallel Processing

Parallel processing is available; enable it via `extra.linters.phpstan.parallel` (bool, int, or object).

## CI/CD Integration

### GitHub Actions

```yaml
- name: PHPStan
  run: composer phpstan
```

### GitLab CI

```yaml
phpstan:
  script:
    - composer phpstan
```

## Best Practices

1. **Start with a baseline** for existing projects and reduce it over time
2. **Limit scope early**, then expand `paths` gradually
3. **Fix errors immediately** in new code
4. **Run in CI/CD** to prevent regressions
5. **Don't ignore errors** unless absolutely necessary

## Learn More

- [PHPStan Documentation](https://phpstan.org/)
- [Configuration Reference](https://phpstan.org/config-reference)
- [Rule Levels](https://phpstan.org/user-guide/rule-levels)
