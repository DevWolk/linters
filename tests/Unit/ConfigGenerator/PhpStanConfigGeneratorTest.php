<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\ConfigGenerator;

use Linters\ConfigGenerator\PhpStanConfigGenerator;
use Linters\Utils\ConfigurationLoader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PhpStanConfigGeneratorTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = sys_get_temp_dir() . '/linters-phpstan-' . uniqid('', true);
        mkdir($this->testDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->testDir)) {
            $this->removeDirectory($this->testDir);
        }
    }

    public function testGenerateWritesConfigurationWithIncludesAndExcludes(): void
    {
        $this->createComposerJson([
            'phpstan' => [
                'paths' => ['/src', '/tests'],
                'skip' => ['/vendor'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpStanConfigGenerator($loader);
        $targetPath = $this->testDir . '/phpstan.neon';

        $generator->generate($targetPath);

        $contents = file_get_contents($targetPath);
        self::assertStringContainsString("- src\n", $contents);
        self::assertStringContainsString("- tests\n", $contents);
        self::assertStringContainsString("excludePaths:\n", $contents);
        self::assertStringContainsString("- vendor\n", $contents);
        self::assertStringContainsString("includes:\n", $contents);
        self::assertStringContainsString("configs/phpstan.neon\n", $contents);
        self::assertStringNotContainsString($this->testDir . '/src', $contents);
    }

    public function testGenerateThrowsWhenPathsMissing(): void
    {
        $this->createComposerJson([
            'phpstan' => [
                'skip' => ['/vendor'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpStanConfigGenerator($loader);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required config: extra.linters.phpstan.paths');

        $generator->generate($this->testDir . '/phpstan.neon');
    }

    private function createComposerJson(array $lintersConfig): void
    {
        $json = json_encode([
            'extra' => [
                'linters' => $lintersConfig,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents($this->testDir . '/composer.json', $json);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
