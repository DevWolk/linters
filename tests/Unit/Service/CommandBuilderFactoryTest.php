<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\Service;

use Generator;
use Linters\CommandBuilder\ComposerNormalizeCommandBuilder;
use Linters\CommandBuilder\ComposerUnusedCommandBuilder;
use Linters\CommandBuilder\PhpCsCommandBuilder;
use Linters\CommandBuilder\PhpCsFixerCommandBuilder;
use Linters\CommandBuilder\PhpMdCommandBuilder;
use Linters\CommandBuilder\PhpStanCommandBuilder;
use Linters\CommandBuilder\RectorCommandBuilder;
use Linters\Enum\Tool;
use Linters\Service\CommandBuilderFactory;
use Linters\Tests\TestCase;
use Linters\Utils\ConfigurationLoader;
use PHPUnit\Framework\Attributes\DataProvider;

final class CommandBuilderFactoryTest extends TestCase
{
    private CommandBuilderFactory $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createComposerJson([]);
        $this->service = new CommandBuilderFactory(new ConfigurationLoader($this->testDir));
    }

    /**
     * @param class-string $expectedClass
     */
    #[DataProvider('provideToolMappings')]
    public function testCreateReturnsBuilderForTool(Tool $tool, string $expectedClass): void
    {
        self::assertInstanceOf($expectedClass, $this->service->create($tool));
    }

    /**
     * @return Generator<string, array{Tool, class-string}>
     */
    public static function provideToolMappings(): Generator
    {
        yield 'Rector' => [Tool::RECTOR, RectorCommandBuilder::class];
        yield 'PHP-CS-Fixer' => [Tool::PHP_CS_FIXER, PhpCsFixerCommandBuilder::class];
        yield 'PHPStan' => [Tool::PHP_STAN, PhpStanCommandBuilder::class];
        yield 'PHPCS' => [Tool::PHP_CS, PhpCsCommandBuilder::class];
        yield 'PHPCBF' => [Tool::PHP_CBF, PhpCsCommandBuilder::class];
        yield 'PHPMD' => [Tool::PHP_MD, PhpMdCommandBuilder::class];
        yield 'composer-unused' => [Tool::COMPOSER_UNUSED, ComposerUnusedCommandBuilder::class];
        yield 'composer-normalize' => [Tool::COMPOSER_NORMALIZE, ComposerNormalizeCommandBuilder::class];
    }
}
