# Custom Rector Rules Documentation

This library provides custom Rector rules designed to improve code quality and enforce best practices in PHP projects.

## Available Custom Rules

### 1. AssertInstanceToStaticCallRector

**Purpose**: Converts PHPUnit assertion instance calls (`$this->assert*()`) to static calls (`self::assert*()`).

**Why**: Static assertions are preferred in modern PHPUnit as they:
- Make it clear that assertions don't depend on test instance state
- Align with PHPUnit best practices
- Are more explicit about the static nature of assertions

#### Supported Methods

- `assertInstanceOf`, `assertNotInstanceOf`
- `assertContains`, `assertNotContains`
- `markTestSkipped`
- `assertFalse`, `assertTrue`
- `assertSame`, `assertEquals`
- `assertCount`
- `assertNull`, `assertNotNull`
- `assertEmpty`, `assertNotEmpty`
- `assertIsString`, `assertIsArray`
- `assertArrayHasKey`, `assertArrayNotHasKey`
- `assertDatabaseTable` (Laravel)

#### Examples

**Before:**
```php
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User('John');

        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->isActive());
        $this->assertSame('John', $user->getName());
        $this->assertCount(0, $user->getPosts());
    }
}
```

**After:**
```php
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User('John');

        self::assertInstanceOf(User::class, $user);
        self::assertTrue($user->isActive());
        self::assertSame('John', $user->getName());
        self::assertCount(0, $user->getPosts());
    }
}
```

#### Configuration

This rule is automatically applied. To disable it, skip it in your Rector configuration:

```php
// rector.php
use Linters\Rector\Rules\AssertInstanceToStaticCallRector;

return RectorConfig::configure()
    ->withSkip([
        AssertInstanceToStaticCallRector::class,
    ]);
```

---

### 2. MockObjectStaticToInstanceCallRector

**Purpose**: Converts PHPUnit mock expectation static calls (`self::any()`) to instance calls (`$this->any()`).

**Why**: Mock expectations should use instance methods because:
- They're part of the test context
- They work with the test's internal mock builder
- This is the PHPUnit recommended approach

#### Supported Methods

- `any()`
- `once()`, `never()`
- `atLeast()`, `atLeastOnce()`
- `atMost()`
- `exactly()`

#### Examples

**Before:**
```php
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function testSendEmail(): void
    {
        $mailer = $this->createMock(Mailer::class);

        $mailer->expects(self::once())
            ->method('send')
            ->with(self::isInstanceOf(Email::class))
            ->willReturn(true);

        $mailer->expects(self::never())
            ->method('sendBatch');

        $service = new UserService($mailer);
        $service->notifyUser($user);
    }
}
```

**After:**
```php
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function testSendEmail(): void
    {
        $mailer = $this->createMock(Mailer::class);

        $mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class))
            ->willReturn(true);

        $mailer->expects($this->never())
            ->method('sendBatch');

        $service = new UserService($mailer);
        $service->notifyUser($user);
    }
}
```

#### Configuration

This rule is automatically applied. To disable:

```php
// rector.php
use Linters\Rector\Rules\MockObjectStaticToInstanceCallRector;

return RectorConfig::configure()
    ->withSkip([
        MockObjectStaticToInstanceCallRector::class,
    ]);
```

---

## Minimum PHP Version

Both custom rules require PHP 8.2+. They won't be applied if your project uses an earlier PHP version.

## How Custom Rules Work

### Rule Registration

Custom rules are registered via the `AppRectorSetList::APP_RULES` set in `configs/rector.php`:

```php
use Linters\Rector\Set\AppRectorSetList;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        AppRectorSetList::APP_RULES,
    ]);
};
```

### Rule Execution

Rules are executed automatically when you run:

```bash
composer rector
```

Or check changes without applying:

```bash
composer rector-check
```

### Rules Applied Only to Test Files

Both rules use `TestsNodeAnalyzer` to ensure they only affect PHPUnit test classes. They won't modify:
- Regular application code
- Non-test classes
- Files without PHPUnit base class

## Creating Your Own Custom Rules

Want to create your own Rector rules? Here's a template:

### Step 1: Create Rule Class

```php
<?php

declare(strict_types=1);

namespace Linters\Rector\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class YourCustomRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Description of what your rule does',
            [
                new CodeSample(
                    // Before
                    <<<'CODE_SAMPLE'
// Before code
CODE_SAMPLE
                    ,
                    // After
                    <<<'CODE_SAMPLE'
// After code
CODE_SAMPLE
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        // Your refactoring logic here
        // Return null if no changes
        // Return new Node if changes were made

        return null;
    }
}
```

### Step 2: Register Rule

Add to `configs/rector.php`:

```php
use Linters\Rector\Rules\YourCustomRector;

return RectorConfig::configure()
    ->withRules([
        YourCustomRector::class,
    ]);
```

### Step 3: Test Rule

Create a test to verify your rule works:

```php
<?php

namespace Linters\Tests\Rector\Rules;

use Iterator;
use Linters\Rector\Rules\YourCustomRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class YourCustomRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
```

## Best Practices

### 1. Rule Scope

Keep rules focused on a single responsibility:
- ✅ Convert specific method calls
- ❌ Multiple unrelated transformations

### 2. Safety First

Ensure rules don't break code:
- Check node types carefully
- Validate transformations
- Add comprehensive tests

### 3. Performance

Optimize rule performance:
- Early returns for non-matching nodes
- Efficient node traversal
- Minimize expensive operations

### 4. Documentation

Document your rules:
- Clear description
- Before/after examples
- Configuration options
- Known limitations

## Debugging Custom Rules

### Enable Debug Mode

```bash
./vendor/bin/rector process --config=./vendor/devwolk/linters/configs/rector.php --debug
```

### Check Rule is Registered

```bash
./vendor/bin/rector list | grep YourCustomRector
```

### Test on Single File

```bash
./vendor/bin/rector process path/to/TestFile.php --config=... --dry-run
```

## Contributing Custom Rules

Want to contribute your custom rule to this library?

1. Fork the repository
2. Create your rule in `src/Rector/Rules/`
3. Add comprehensive tests
4. Update this documentation
5. Submit a pull request

## Learn More

- [Rector Custom Rules Documentation](https://getrector.com/documentation/custom-rules)
- [PHP-Parser Documentation](https://github.com/nikic/PHP-Parser)
- [AST Explorer](https://php-parser-ast-explorer.com/)

## Troubleshooting

### Rule Not Applied

**Possible causes:**
1. Rule is in skip list
2. PHP version requirement not met
3. Rule conditions not matched
4. Cache not cleared

**Solutions:**
```bash
# Clear cache
composer rector -- --clear-cache

# Check PHP version in rector.php
# Verify rule is registered
# Check rule conditions
```

### Unexpected Transformations

**Solution**: Test rule in isolation:

```bash
./vendor/bin/rector process tests/YourTest.php --config=... --dry-run --debug
```

Inspect the output to see exactly what changed and why.
