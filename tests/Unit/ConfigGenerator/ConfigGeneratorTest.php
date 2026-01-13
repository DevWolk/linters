<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\ConfigGenerator;

use InvalidArgumentException;
use Linters\ConfigGenerator\ConfigGeneratorInterface;
use Linters\ConfigGenerator\PhpCsConfigGenerator;
use Linters\ConfigGenerator\PhpMdConfigGenerator;
use Linters\ConfigGenerator\PhpStanConfigGenerator;
use Linters\Tests\TestCase;
use Linters\Utils\ConfigurationLoader;
use PHPUnit\Framework\Attributes\DataProvider;

final class ConfigGeneratorTest extends TestCase
{
    /**
     * @param class-string<ConfigGeneratorInterface> $generatorClass
     */
    #[DataProvider('generatorsRequiringPathsProvider')]
    public function testGenerateThrowsWhenPathsMissing(
        string $toolKey,
        string $generatorClass,
        string $targetFile,
    ): void {
        $this->createComposerJson([
            $toolKey => [
                'skip_dirs' => ['vendor'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new $generatorClass($loader);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Missing required config: extra.linters.%s.paths', $toolKey));

        $generator->generate($this->testDir . '/' . $targetFile);
    }

    /**
     * @return iterable<string, array{string, class-string<ConfigGeneratorInterface>, string}>
     */
    public static function generatorsRequiringPathsProvider(): iterable
    {
        yield 'phpstan' => ['phpstan', PhpStanConfigGenerator::class, 'phpstan.neon'];
        yield 'phpcs' => ['phpcs', PhpCsConfigGenerator::class, 'phpcs.xml'];
        yield 'phpmd' => ['phpmd', PhpMdConfigGenerator::class, 'phpmd.ruleset.xml'];
    }
}
