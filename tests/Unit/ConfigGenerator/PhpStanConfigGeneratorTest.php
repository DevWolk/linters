<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\ConfigGenerator;

use Linters\ConfigGenerator\PhpStanConfigGenerator;
use Linters\Tests\TestCase;
use Linters\Utils\ConfigurationLoader;

use function Safe\file_get_contents;

final class PhpStanConfigGeneratorTest extends TestCase
{
    public function testGenerateWritesConfiguration(): void
    {
        $this->createComposerJson([
            'phpstan' => [
                'paths'     => ['src', 'tests'],
                'skip-dirs' => ['vendor'],
                'cache-dir' => '.cache/phpstan',
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpStanConfigGenerator($loader);
        $targetPath = $this->testDir . '/phpstan.neon';

        $generator->generate($targetPath);

        $contents = file_get_contents($targetPath);

        // paths
        self::assertStringContainsString("- src\n", $contents);
        self::assertStringContainsString("- tests\n", $contents);

        // excludes
        self::assertStringContainsString("excludePaths:\n", $contents);
        self::assertStringContainsString("- vendor\n", $contents);

        // includes package config
        self::assertStringContainsString("includes:\n", $contents);
        self::assertStringContainsString("configs/phpstan.neon\n", $contents);

        // cache dir
        self::assertStringContainsString('tmpDir: .cache/phpstan', $contents);

        // no absolute paths
        self::assertStringNotContainsString($this->testDir, $contents);
    }
}
