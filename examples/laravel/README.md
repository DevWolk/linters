# Laravel Integration Example

This example shows how to integrate `devwolk/linters` into a Laravel 11 project.

## Installation

```bash
composer require --dev devwolk/linters
```

## Configuration

Copy the `composer.json` configuration from this example to your Laravel project's `composer.json`.

### Key Configuration Points

1. **Rector paths**: Include Laravel-specific directories
   - `/app` - Application code
   - `/config` - Configuration files
   - `/database` - Migrations, seeders, factories
   - `/routes` - Route definitions
   - `/tests` - Test files

2. **Skip patterns**: Exclude Laravel framework files
   - Trust middleware (rarely modified)
   - Bootstrap files
   - Storage directory

3. **Blade templates**: Excluded from PHP-CS-Fixer and PHPCS
   ```json
   "skip": ["*.blade.php"]
   ```

4. **Psalm plugin**: Laravel-specific type checking
   ```json
   "config": {
     "plugins": {
       "pluginClass": [
         {"class": "Psalm\\LaravelPlugin\\Plugin"}
       ]
     }
   }
   ```

## Usage

### Run All Linters (Check)

```bash
composer lint
```

This runs:
- Rector (dry-run)
- PHP-CS-Fixer (dry-run)
- PHPStan
- PHP_CodeSniffer

### Auto-fix Issues

```bash
composer lint-fix
```

This applies:
- Rector fixes
- PHP-CS-Fixer fixes

### Individual Tools

```bash
# Check code quality
composer rector-check
composer phpstan

# Fix code style
composer rector
composer php-cs-fixer

# Run static analysis
composer phpcs
composer phpmd
```

## Laravel-Specific Tips

### 1. Eloquent Models

PHPStan and Psalm understand Eloquent models with proper docblocks:

```php
/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon $created_at
 */
class User extends Model
{
    // ...
}
```

### 2. Service Container

For dependency injection analysis, ensure classes are properly type-hinted:

```php
public function __construct(
    private UserRepository $users,
    private Logger $log
) {
}
```

### 3. Facades

Use facade docblocks or IDE helper:

```bash
composer require --dev barryvdh/laravel-ide-helper
php artisan ide-helper:generate
```

### 4. Routes

Rector may suggest changes to routes - review carefully:

```php
// Rector might suggest arrow functions
Route::get('/users', fn() => User::all());

// But named controllers are often better for Laravel
Route::get('/users', [UserController::class, 'index']);
```

## CI/CD Integration

### GitHub Actions

Create `.github/workflows/lint.yml`:

```yaml
name: Linters

on: [push, pull_request]

jobs:
  lint:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, xml, ctype, json

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run linters
        run: composer lint
```

## Common Issues

### Issue: Rector breaks routes

Some Rector rules may suggest changes that don't work well with Laravel routes.

**Solution**: Skip problematic rules or be selective about which changes to apply.

### Issue: Too many PHPStan errors in tests

**Solution**: Use baseline for existing tests:

```bash
composer phpstan-baseline
```

### Issue: Blade syntax errors

**Solution**: Blade templates are automatically excluded. If you see errors, ensure `*.blade.php` is in skip patterns.

## Learn More

- [devwolk/linters Documentation](../../README.md)
- [Laravel Testing](https://laravel.com/docs/testing)
- [Larastan](https://github.com/nunomaduro/larastan)
