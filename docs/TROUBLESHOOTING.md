# Troubleshooting Guide

Common issues and their solutions when using the linters library.

## Installation Issues

### Composer Install Fails

**Error:**
```
The requested package devwolk/linters could not be found
```

**Solutions:**
1. Ensure you're using `--dev` flag:
   ```bash
   composer require --dev devwolk/linters
   ```

2. Update Composer:
   ```bash
   composer self-update
   ```

3. Clear Composer cache:
   ```bash
   composer clear-cache
   ```

### Memory Limit Exceeded

**Error:**
```
Fatal error: Allowed memory size exhausted
```

**Solutions:**
1. Increase PHP memory limit:
   ```bash
   php -d memory_limit=2G /usr/bin/composer install
   ```

2. Or permanently in `php.ini`:
   ```ini
   memory_limit = 2G
   ```

## Configuration Issues

### Paths Not Found

**Error:**
```
Path "/app" does not exist
```

**Cause**: Paths must be absolute from project root with leading slash.

**Solution:**
```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/app"],  // ✅ Correct
        // NOT "app"          // ❌ Wrong
        // NOT "./app"        // ❌ Wrong
      }
    }
  }
}
```

### Configuration Not Loaded

**Symptoms**: Tool ignores your paths configuration

**Solutions:**
1. Verify JSON syntax:
   ```bash
   composer validate
   ```

2. Ensure configuration is in ROOT `composer.json`

3. Clear autoload cache:
   ```bash
   composer dump-autoload
   ```

## Rector Issues

### Changes Not Applied

**Solutions:**
1. Clear cache:
   ```bash
   ./vendor/bin/rector process --clear-cache
   ```

2. Check file permissions:
   ```bash
   chmod -R 755 app/
   ```

3. Verify dry-run vs actual run:
   ```bash
   composer rector-check  # Shows changes
   composer rector        # Applies changes
   ```

### Out of Memory

**Solutions:**
```bash
php -d memory_limit=2G vendor/bin/rector process
```

### Infinite Loop Detected

**Cause**: Conflicting rules

**Solution**: Skip problematic rules:
```php
// Create custom rector.php
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withConfiguredRule(YourRule::class, [])
    ->withSkip([
        ProblematicRule::class,
    ]);
```

## PHPStan Issues

### Too Many Errors

**Solutions:**
1. Generate baseline:
   ```bash
   composer phpstan-baseline
   ```

2. Lower analysis level:
   ```json
   {
     "extra": {
       "linters": {
         "phpstan": {
           "level": 5
         }
       }
     }
   }
   ```

3. Ignore specific errors:
   ```neon
   # phpstan.neon
   parameters:
       ignoreErrors:
           - '#Method .* has no return type#'
   ```

### "Class not found" Errors

**Cause**: Autoload not configured

**Solutions:**
1. Run composer dump-autoload:
   ```bash
   composer dump-autoload
   ```

2. Add bootstrap file:
   ```neon
   # phpstan.neon
   parameters:
       bootstrapFiles:
           - vendor/autoload.php
   ```

## PHP-CS-Fixer Issues

### Changes Not Applied

**Solutions:**
1. Remove cache:
   ```bash
   rm -rf .php-cs-fixer.cache
   ```

2. Run without cache:
   ```bash
   composer php-cs-fixer -- --using-cache=no
   ```

### File Permission Errors

**Solution:**
```bash
chmod -R 755 src/
```

## Psalm Issues

### Configuration Not Generated

**Error:**
```
psalm.xml not found
```

**Cause**: Psalm uses a generator script

**Solution**: Ensure the full script runs:
```bash
php ./vendor/devwolk/linters/src/ConfigGenerator/PsalmConfigGenerator.php --target=./psalm.xml
./vendor/bin/psalm --config=./psalm.xml
rm ./psalm.xml
```

### Plugin Not Loaded

**Error:**
```
Plugin class not found
```

**Solution**: Install plugin first:
```bash
composer require --dev psalm/plugin-laravel
```

Then configure:
```json
{
  "extra": {
    "linters": {
      "psalm": {
        "config": {
          "plugins": {
            "pluginClass": [
              {"class": "Psalm\\LaravelPlugin\\Plugin"}
            ]
          }
        }
      }
    }
  }
}
```

## Performance Issues

### Slow Analysis

**Solutions:**

1. **Limit scope**:
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

2. **Use parallel processing** (already enabled by default)

3. **Increase memory**:
   ```bash
   php -d memory_limit=2G vendor/bin/phpstan analyze
   ```

4. **Exclude large directories**:
   ```json
   {
     "extra": {
       "linters": {
         "rector": {
           "skip": ["/vendor", "/storage", "/node_modules"]
         }
       }
     }
   }
   ```

## CI/CD Issues

### CI Fails Locally Works

**Causes**:
1. Different PHP versions
2. Different dependencies
3. Cache differences

**Solutions:**
1. Match PHP versions:
   ```yaml
   # .github/workflows/ci.yml
   - uses: shivammathur/setup-php@v2
     with:
       php-version: '8.2'  # Match local version
   ```

2. Clear cache in CI:
   ```yaml
   - run: composer rector -- --clear-cache
   ```

3. Use same Composer flags:
   ```yaml
   - run: composer install --no-interaction --prefer-dist
   ```

## Docker Issues

### Permission Denied

**Error:**
```
Permission denied when writing to /app
```

**Solution**: Run as correct user:
```dockerfile
RUN chown -R www-data:www-data /app
USER www-data
```

Or use volume permissions:
```yaml
# docker-compose.yml
volumes:
  - ./:/app:rw
```

## Windows Issues

### Path Separators

**Error**: Windows uses `\` instead of `/`

**Solution**: Always use `/` in configuration:
```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/app/Services"]  // Works on all OSes
      }
    }
  }
}
```

### Line Endings

**Error**: `^M` characters in files

**Solution**: Configure git:
```bash
git config --global core.autocrlf input
```

## Getting Help

If you're still experiencing issues:

1. **Check documentation**:
   - [INSTALLATION.md](./INSTALLATION.md)
   - [CONFIGURATION.md](./CONFIGURATION.md)
   - Tool-specific guides

2. **Enable debug mode**:
   ```bash
   composer rector -- --debug
   composer phpstan -- -vvv
   ```

3. **Check tool documentation**:
   - [Rector Docs](https://getrector.com/documentation)
   - [PHPStan Docs](https://phpstan.org/)
   - [PHP-CS-Fixer Docs](https://cs.symfony.com/)

4. **Report issues**:
   - GitHub Issues: [devwolk/linters](https://github.com/devwolk/linters/issues)
   - Include: error message, PHP version, tool versions, configuration
