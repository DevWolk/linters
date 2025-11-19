# devwolk/linters

Centralized PHP linter configurations and static analysis tools for PHP projects.

## 📋 Overview

`devwolk/linters` provides a unified, opinionated set of configurations for the most popular PHP code quality tools. Instead of maintaining separate configurations across multiple projects, install this package and configure everything through your `composer.json`.

## ✨ Features

- **Centralized Configuration**: Single source of truth for all linter settings
- **Dynamic Path Configuration**: Configure paths through `composer.json` extra section
- **Production-Ready Configs**: Battle-tested configurations for PHP 8.2+
- **Framework Support**: Optimized for Laravel and Symfony
- **Custom Rector Rules**: Additional code quality rules
- **Comprehensive Documentation**: Detailed guides for each tool

## 🛠 Supported Tools

| Tool | Version | Purpose |
|------|---------|---------|
| [Rector](https://getrector.com/) | ^2.2 | Automated code refactoring and upgrades |
| [PHP-CS-Fixer](https://cs.symfony.com/) | ^3.89 | Code style formatting |
| [PHPStan](https://phpstan.org/) | ^2.1 | Static type analysis |
| [Psalm](https://psalm.dev/) | ^5.0 | Alternative static analyzer |
| [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) | ^4.0 | Coding standards validation |
| [PHPMD](https://phpmd.org/) | ^2.15 | Mess detection |
| [composer-unused](https://github.com/composer-unused/composer-unused) | ^0.9 | Unused dependency detection |

## 📦 Requirements

| Requirement | Version |
|------------|---------|
| PHP | ^8.2 |
| Composer | ^2.0 |
| OS | Unix/Linux/macOS |

## 🚀 Quick Start

### 1. Install

```bash
composer require --dev devwolk/linters
```

### 2. Configure

Add to your `composer.json`:

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/app", "/src", "/tests"],
        "skip": ["/vendor"],
        "frameworks": ["laravel"]
      },
      "php-cs-fixer": {
        "paths": ["/app", "/src"],
        "skip": []
      },
      "phpstan": {
        "paths": ["/app", "/src"],
        "level": 8
      }
    }
  }
}
```

### 3. Add Scripts

```json
{
  "scripts": {
    "rector": "./vendor/bin/rector process --config=./vendor/devwolk/linters/configs/rector.php",
    "phpstan": "./vendor/bin/phpstan analyze --configuration=./vendor/devwolk/linters/configs/phpstan.neon",
    "php-cs-fixer": "./vendor/bin/php-cs-fixer fix --config=./vendor/devwolk/linters/configs/.php-cs-fixer.dist.php"
  }
}
```

### 4. Run

```bash
composer rector
composer phpstan
composer php-cs-fixer
```

## 📚 Documentation

- **[Installation Guide](docs/INSTALLATION.md)** - Detailed installation and setup
- **[Configuration Guide](docs/CONFIGURATION.md)** - Complete configuration reference
- **[Rector Guide](docs/RECTOR_GUIDE.md)** - Using Rector for code upgrades
- **[PHPStan Guide](docs/PHPSTAN_GUIDE.md)** - Static analysis with PHPStan
- **[Custom Rector Rules](docs/CUSTOM_RECTOR_RULES.md)** - Library's custom rules
- **[Troubleshooting](docs/TROUBLESHOOTING.md)** - Common issues and solutions

## 🎯 Framework Examples

### Laravel

See [examples/laravel](examples/laravel/) for complete Laravel integration.

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/app", "/config", "/database", "/tests"],
        "skip": ["/bootstrap", "/storage"]
      },
      "php-cs-fixer": {
        "paths": ["/app", "/config"],
        "skip": ["*.blade.php"]
      }
    }
  }
}
```

### Symfony

See [examples/symfony](examples/symfony/) for complete Symfony integration.

```json
{
  "extra": {
    "linters": {
      "rector": {
        "paths": ["/src", "/tests"],
        "skip": ["/src/Kernel.php", "/var"]
      },
      "php-cs-fixer": {
        "paths": ["/src"],
        "skip": ["*.twig"]
      }
    }
  }
}
```

## 🎨 Custom Rector Rules

This library includes custom Rector rules:

### AssertInstanceToStaticCallRector

Converts PHPUnit assertions from `$this->assert*()` to `self::assert*()`:

```php
// Before
$this->assertInstanceOf(User::class, $user);
$this->assertTrue($value);

// After
self::assertInstanceOf(User::class, $user);
self::assertTrue($value);
```

### MockObjectStaticToInstanceCallRector

Converts mock expectations from `self::once()` to `$this->once()`:

```php
// Before
$mock->expects(self::once())->method('send');

// After
$mock->expects($this->once())->method('send');
```

See [docs/CUSTOM_RECTOR_RULES.md](docs/CUSTOM_RECTOR_RULES.md) for complete documentation.

## 🏗️ Architecture

### Configuration Loader

Dynamic configuration loading through `ConfigurationLoader` class:

```php
use Linters\Utils\ConfigurationLoader;

$loader = new ConfigurationLoader();
$paths = $loader->getAbsolutePaths('rector.paths'); // ['/app', '/src']
```

### Config Generators

Automated configuration generation for non-PHP config files:

- `PsalmConfigGenerator` - Generates `psalm.xml`
- `PhpStanConfigGenerator` - Generates `phpstan.neon`
- `PhpCsConfigGenerator` - Generates `phpcs.xml`
- `PhpMdConfigGenerator` - Generates `phpmd.ruleset.xml`

### Framework Presets

Enable opinionated framework rule sets via `extra.linters.rector.frameworks`. Accepts either an array of framework names (e.g., `["laravel"]`, `["symfony"]`, or both) or an object map (`{"laravel": true, "symfony": false}`). Only the frameworks you list are loaded; omitting the key defaults to Laravel for backward compatibility.

## 🔄 Typical Workflow

```bash
# 1. Check what would change (dry-run)
composer rector -- --dry-run
composer php-cs-fixer -- --dry-run

# 2. Review and apply changes
composer rector
composer php-cs-fixer

# 3. Run static analysis
composer phpstan
composer psalm

# 4. Check coding standards
composer phpcs
```

## 🛡️ CI/CD Integration

### GitHub Actions

```yaml
name: Code Quality

on: [push, pull_request]

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: composer rector -- --dry-run
      - run: composer phpstan
      - run: composer php-cs-fixer -- --dry-run
```

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

This library builds upon excellent tools created by:

- [Rector](https://getrector.com/) team
- [PHPStan](https://phpstan.org/) team
- [PHP-CS-Fixer](https://cs.symfony.com/) team
- [Psalm](https://psalm.dev/) team
- And many other open-source contributors

## 📞 Support

- **Documentation**: [docs/](docs/)
- **Issues**: [GitHub Issues](https://github.com/devwolk/linters/issues)
- **Examples**: [examples/](examples/)

## 🗺️ Roadmap

- [ ] Add PHPUnit configuration
- [ ] Create web-based configuration generator
- [ ] Add more custom Rector rules
- [ ] Support for additional frameworks (CakePHP, CodeIgniter)
- [ ] IDE integration guides

---

Made with ❤️ for the PHP community
