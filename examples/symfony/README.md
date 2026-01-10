# Symfony Integration Example

This example shows how to integrate `devwolk/linters` into a Symfony 7 project.

## Installation

```bash
composer require --dev devwolk/linters
```

## Configuration

Copy the `composer.json` configuration from this example to your Symfony project's `composer.json`.

### Key Configuration Points

1. **Rector paths**: Symfony project structure
   - `/src` - Application code
   - `/tests` - Test files

2. **Skip patterns**: Exclude Symfony framework files
   - `Kernel.php` (framework file, rarely modified)
   - `/var` directory (cache, logs)
   - `/vendor` directory

3. **Twig templates**: Exclude the templates directory for tools that scan PHP files
   ```json
   "skip": ["/templates"]
   ```
   For PHP-CS-Fixer, use `php-cs-fixer.skip_dirs` when you need directory-level exclusions.

## Usage

### Run All Linters (Check)

```bash
composer lint
```

### Auto-fix Issues

```bash
composer lint-fix
```

### Individual Tools

```bash
# Static analysis
composer rector-check
composer phpstan
composer phpcs

# Auto-fix
composer rector
composer php-cs-fixer
```

## Symfony-Specific Tips

### 1. Service Autowiring

Ensure services have proper type hints for autowiring:

```php
namespace App\Service;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {
    }
}
```

### 2. Controllers

Use attributes and type hints:

```php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/users', name: 'users_list')]
    public function list(UserRepository $repository): Response
    {
        return $this->render('user/list.html.twig', [
            'users' => $repository->findAll(),
        ]);
    }
}
```

### 3. Entities

Document entity properties for better analysis:

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    // ...
}
```

### 4. Form Types

Properly type hint form builders:

```php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('username');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
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
          extensions: mbstring, xml, ctype, json, intl

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run linters
        run: composer lint
```

### GitLab CI

Create `.gitlab-ci.yml`:

```yaml
lint:
  image: php:8.2
  before_script:
    - apt-get update && apt-get install -y git zip unzip
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install --no-interaction
  script:
    - php composer.phar lint
```

## Common Issues

### Issue: Rector suggests changes to Kernel

**Solution**: Kernel.php is already in skip list. If you see suggestions, they're usually safe but review carefully.

### Issue: PHPStan errors in generated code

**Solution**: Add generated directories to skip:

```json
{
  "extra": {
    "linters": {
      "phpstan": {
        "skip": ["/vendor", "/var", "/src/Generated"]
      }
    }
  }
}
```

### Issue: Twig syntax errors

**Solution**: Exclude `/templates` in `skip` for PHPCS/PHPMD (and any PHP-only tools).

## Additional Tools for Symfony

Consider also installing:

```bash
# PHPStan Symfony extension (auto-installed with phpstan/extension-installer)
composer require --dev phpstan/phpstan-symfony

# Symfony Insight for code quality
# Available at https://insight.symfony.com/
```

## Learn More

- [devwolk/linters Documentation](../../README.md)
- [Symfony Best Practices](https://symfony.com/doc/current/best_practices.html)
- [Symfony Coding Standards](https://symfony.com/doc/current/contributing/code/standards.html)
