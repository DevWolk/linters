<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\ConfigGenerator;

use DOMDocument;
use DOMNodeList;
use InvalidArgumentException;
use Linters\ConfigGenerator\Contracts\ConfigGeneratorInterface;
use Linters\ConfigGenerator\PhpCsConfigGenerator;
use Linters\ConfigGenerator\PhpMdConfigGenerator;
use Linters\ConfigGenerator\PhpStanConfigGenerator;
use Linters\Tests\TestCase;
use Linters\Utils\ConfigurationLoader;
use PHPUnit\Framework\Attributes\DataProvider;

use function Safe\file_get_contents;

final class ConfigGeneratorTest extends TestCase
{
    /**
     * @param class-string<ConfigGeneratorInterface> $generatorClass
     */
    #[DataProvider('missingPathsProvider')]
    public function testGenerateThrowsWhenPathsMissing(
        string $toolKey,
        string $generatorClass,
        string $targetFile,
    ): void {
        $this->createComposerJson([
            $toolKey => ['skip-dirs' => ['vendor']],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new $generatorClass($loader);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            \sprintf('Missing required "paths" in extra.linters.%s', $toolKey),
        );

        $generator->generate($this->testDir . '/' . $targetFile);
    }

    /**
     * @return iterable<string, array{string, class-string<ConfigGeneratorInterface>, string}>
     */
    public static function missingPathsProvider(): iterable
    {
        yield 'phpstan' => ['phpstan', PhpStanConfigGenerator::class, 'phpstan.neon'];
        yield 'phpcs' => ['phpcs', PhpCsConfigGenerator::class, 'phpcs.xml'];
        yield 'phpmd' => ['phpmd', PhpMdConfigGenerator::class, 'phpmd.ruleset.xml'];
    }

    /**
     * @param array<string, mixed> $config
     * @param string[]             $contains
     * @param string[]             $notContains
     */
    #[DataProvider('phpStanProvider')]
    public function testPhpStanGenerate(
        array $config,
        array $contains,
        array $notContains = [],
    ): void {
        $this->createComposerJson(['phpstan' => $config]);

        $neon = $this->generatePhpStan();

        foreach ($contains as $expected) {
            self::assertStringContainsString($expected, $neon);
        }

        foreach ($notContains as $unexpected) {
            self::assertStringNotContainsString($unexpected, $neon);
        }
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string[], 2?: string[]}>
     */
    public static function phpStanProvider(): iterable
    {
        yield 'paths' => [
            ['paths' => ['src', 'tests']],
            ["    - src\n", "    - tests\n"],
        ];

        yield 'exclude paths from skip-dirs and skip-files' => [
            [
                'paths' => ['src'],
                'skip-dirs' => ['vendor', 'storage'],
                'skip-files' => ['config/ide-helper.php'],
            ],
            ["excludePaths:\n", "    - vendor\n", "    - storage\n", "    - config/ide-helper.php\n"],
        ];

        yield 'omits excludePaths when no skips' => [
            ['paths' => ['src']],
            ["    - src\n"],
            ['excludePaths:'],
        ];

        yield 'baseline included in includes' => [
            ['paths' => ['src'], 'baseline' => 'phpstan-baseline.neon'],
            ["    - phpstan-baseline.neon\n"],
        ];

        yield 'cache-dir maps to tmpDir' => [
            ['paths' => ['src'], 'cache-dir' => '/tmp/phpstan'],
            ["tmpDir: /tmp/phpstan\n"],
        ];

        yield 'omits tmpDir when no cache-dir' => [
            ['paths' => ['src']],
            ["    - src\n"],
            ['tmpDir:'],
        ];

        yield 'parallel with max-processes and timeout' => [
            [
                'paths' => ['src'],
                'parallel' => ['enabled' => true, 'max-processes' => 4, 'timeout' => 300],
            ],
            ["parallel:\n", 'maximumNumberOfProcesses: 4', 'processTimeout: 300'],
        ];

        yield 'omits parallel when disabled' => [
            ['paths' => ['src'], 'parallel' => false],
            ["    - src\n"],
            ['parallel:'],
        ];

        yield 'includes package config' => [
            ['paths' => ['src']],
            ["includes:\n", 'phpstan.neon'],
        ];
    }

    /**
     * @param array<string, mixed>        $config
     * @param string[]                    $expectedFiles
     * @param string[]                    $expectedExcludes
     * @param array<string, list<string>> $expectedRuleExcludes
     */
    #[DataProvider('phpCsProvider')]
    public function testPhpCsGenerate(
        array $config,
        array $expectedFiles = [],
        array $expectedExcludes = [],
        array $expectedRuleExcludes = [],
        ?string $expectedCachePath = null,
    ): void {
        $this->createComposerJson(['phpcs' => $config]);

        $dom = $this->generatePhpCsDom();

        foreach ($expectedFiles as $file) {
            self::assertContains($file, $this->getElementValues($dom, 'file'));
        }

        foreach ($expectedExcludes as $exclude) {
            self::assertContains($exclude, $this->getElementValues($dom, 'exclude-pattern'));
        }

        foreach ($expectedRuleExcludes as $ruleName => $patterns) {
            $this->assertRuleExclude($dom, $ruleName, $patterns);
        }

        if ($expectedCachePath !== null) {
            self::assertSame($expectedCachePath, $this->getArgValue($dom, 'cache'));
        }
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function phpCsProvider(): iterable
    {
        yield 'paths as file elements' => [
            ['paths' => ['src', 'tests']],
            ['src', 'tests'],
        ];

        yield 'exclude patterns from skip-dirs and skip-files' => [
            ['paths' => ['src'], 'skip-dirs' => ['vendor'], 'skip-files' => ['config/generated.php']],
            [],
            ['vendor', 'config/generated.php'],
        ];

        yield 'rule-specific excludes' => [
            [
                'paths' => ['src'],
                'rule-excludes' => [
                    'Generic.Files.LineLength.TooLong' => ['src/SomeLongFile.php'],
                ],
            ],
            [],
            [],
            ['Generic.Files.LineLength.TooLong' => ['src/SomeLongFile.php']],
        ];

        yield 'cache-dir sets cache arg' => [
            ['paths' => ['src'], 'cache-dir' => '/tmp/cache'],
            [],
            [],
            [],
            '/tmp/cache/.phpcs-cache',
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @param string[]             $expectedExcludes
     */
    #[DataProvider('phpMdProvider')]
    public function testPhpMdGenerate(
        array $config,
        array $expectedExcludes,
    ): void {
        $this->createComposerJson(['phpmd' => $config]);

        $dom = $this->generatePhpMdDom();
        $excludes = $this->getElementValues($dom, 'exclude-pattern');

        self::assertCount(\count($expectedExcludes), $excludes);

        foreach ($expectedExcludes as $expected) {
            self::assertContains($expected, $excludes);
        }
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string[]}>
     */
    public static function phpMdProvider(): iterable
    {
        yield 'exclude patterns from skip-dirs and skip-files' => [
            ['paths' => ['src'], 'skip-dirs' => ['vendor', 'storage'], 'skip-files' => ['helpers.php']],
            ['vendor', 'storage', 'helpers.php'],
        ];

        yield 'no excludes when skips are empty' => [
            ['paths' => ['src']],
            [],
        ];
    }

    private function generatePhpStan(): string
    {
        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpStanConfigGenerator($loader);
        $target = $this->testDir . '/phpstan.neon';

        $generator->generate($target);

        return file_get_contents($target);
    }

    private function generatePhpCsDom(): DOMDocument
    {
        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpCsConfigGenerator($loader);
        $target = $this->testDir . '/phpcs.xml';

        $generator->generate($target);

        $dom = new DOMDocument();
        $dom->load($target);

        return $dom;
    }

    private function generatePhpMdDom(): DOMDocument
    {
        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpMdConfigGenerator($loader);
        $target = $this->testDir . '/phpmd.ruleset.xml';

        $generator->generate($target);

        $dom = new DOMDocument();
        $dom->load($target);

        return $dom;
    }

    /**
     * @return list<string>
     */
    private function getElementValues(DOMDocument $dom, string $tagName): array
    {
        /** @var DOMNodeList<\DOMElement> $elements */
        $elements = $dom->getElementsByTagName($tagName);
        $values = [];

        foreach ($elements as $element) {
            $values[] = $element->textContent;
        }

        return $values;
    }

    private function getArgValue(DOMDocument $dom, string $argName): ?string
    {
        foreach ($dom->getElementsByTagName('arg') as $arg) {
            if ($arg->getAttribute('name') === $argName) {
                return $arg->getAttribute('value');
            }
        }

        return null;
    }

    /**
     * @param list<string> $expectedPatterns
     */
    private function assertRuleExclude(
        DOMDocument $dom,
        string $ruleName,
        array $expectedPatterns,
    ): void {
        foreach ($dom->getElementsByTagName('rule') as $rule) {
            if ($rule->getAttribute('ref') !== $ruleName) {
                continue;
            }

            $excludes = $rule->getElementsByTagName('exclude-pattern');
            self::assertSame(\count($expectedPatterns), $excludes->length);

            foreach ($expectedPatterns as $i => $pattern) {
                self::assertSame($pattern, $excludes->item($i)?->textContent);
            }

            return;
        }

        self::fail(\sprintf('Rule exclude for %s not found', $ruleName));
    }
}
