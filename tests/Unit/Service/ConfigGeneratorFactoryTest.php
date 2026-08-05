<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\Service;

use Generator;
use Linters\ConfigGenerator\ComposerUnusedConfigGenerator;
use Linters\ConfigGenerator\PhpCsConfigGenerator;
use Linters\ConfigGenerator\PhpCsFixerConfigGenerator;
use Linters\ConfigGenerator\PhpMdConfigGenerator;
use Linters\ConfigGenerator\PhpStanConfigGenerator;
use Linters\ConfigGenerator\RectorConfigGenerator;
use Linters\Enum\Tool;
use Linters\Service\ConfigGeneratorFactory;
use Linters\Tests\TestCase;
use Linters\Utils\ConfigurationLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

final class ConfigGeneratorFactoryTest extends TestCase
{
    private ConfigGeneratorFactory $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createComposerJson([]);
        $this->service = new ConfigGeneratorFactory(new ConfigurationLoader($this->testDir));
    }

    /**
     * @param class-string $expectedClass
     */
    #[DataProvider('provideToolMappings')]
    public function testCreateReturnsGeneratorForTool(Tool $tool, string $expectedClass): void
    {
        self::assertInstanceOf($expectedClass, $this->service->create($tool));
    }

    /**
     * @return Generator<string, array{Tool, class-string}>
     */
    public static function provideToolMappings(): Generator
    {
        yield 'Rector' => [Tool::RECTOR, RectorConfigGenerator::class];
        yield 'PHP-CS-Fixer' => [Tool::PHP_CS_FIXER, PhpCsFixerConfigGenerator::class];
        yield 'PHPStan' => [Tool::PHP_STAN, PhpStanConfigGenerator::class];
        yield 'PHPCS' => [Tool::PHP_CS, PhpCsConfigGenerator::class];
        yield 'PHPCBF' => [Tool::PHP_CBF, PhpCsConfigGenerator::class];
        yield 'PHPMD' => [Tool::PHP_MD, PhpMdConfigGenerator::class];
        yield 'composer-unused' => [Tool::COMPOSER_UNUSED, ComposerUnusedConfigGenerator::class];
    }

    public function testCreateRejectsToolWithoutConfiguration(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('No generator for this tool');

        $this->service->create(Tool::COMPOSER_NORMALIZE);
    }
}
