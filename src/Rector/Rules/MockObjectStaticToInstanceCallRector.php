<?php

declare(strict_types=1);

namespace Linters\Rector\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Rector\Rector\AbstractRector;
use Rector\ValueObject\PhpVersion;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\Exception\PoorDocumentationException;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class MockObjectStaticToInstanceCallRector extends AbstractRector implements MinPhpVersionInterface
{
    /** @var string[] */
    private const array MOCK_METHODS = ['once', 'never', 'atLeast', 'atLeastOnce', 'atMost', 'exactly'];

    public function __construct(
        private readonly TestsNodeAnalyzer $testsNodeAnalyzer,
    ) {
    }

    /**
     * @throws PoorDocumentationException
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Changes PHPUnit MockObject static calls like self::once() to instance calls like $this->once()',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test(): void
    {
        $mock = $this->createMock(Something::class);
        $mock->expects(self::atLeastOnce())
            ->method('someMethod')
            ->willReturn(true);
            
        $mock->expects(self::once())
            ->method('otherMethod');
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test(): void
    {
        $mock = $this->createMock(Something::class);
        $mock->expects($this->atLeastOnce())
            ->method('someMethod')
            ->willReturn(true);
            
        $mock->expects($this->once())
            ->method('otherMethod');
    }
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [StaticCall::class];
    }

    /**
     * @param StaticCall $node
     *
     * @phpstan-param Node $node
     */
    public function refactor(Node $node): ?Node
    {
        $node = self::requireStaticCall($node);

        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }

        if (!$this->isNames($node->class, ['self', 'static'])) {
            return null;
        }

        if (!$this->isNames($node->name, self::MOCK_METHODS)) {
            return null;
        }

        return new MethodCall(
            new Variable('this'),
            $node->name,
            $node->args,
        );
    }

    public function provideMinPhpVersion(): int
    {
        return PhpVersion::PHP_82;
    }

    private static function requireStaticCall(Node $node): StaticCall
    {
        if (!$node instanceof StaticCall) {
            throw new \LogicException('Rector dispatched an unsupported node type');
        }

        return $node;
    }
}
