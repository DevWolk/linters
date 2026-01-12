<?php

declare(strict_types=1);

namespace Linters\Tests\Unit;

use Linters\Utils\ConfigurationLoader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ConfigurationLoaderTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = sys_get_temp_dir() . '/linters-test-' . uniqid();
        mkdir($this->testDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (is_dir($this->testDir)) {
            $this->removeDirectory($this->testDir);
        }
    }

    public function testConstructorThrowsExceptionWhenComposerJsonNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('composer.json file not found');

        new ConfigurationLoader('/nonexistent/path');
    }

    public function testGetReturnsDefaultWhenKeyNotFound(): void
    {
        $this->createComposerJson([]);

        $loader = new ConfigurationLoader($this->testDir);

        self::assertSame('default', $loader->get('nonexistent.key', 'default'));
    }

    public function testGetReturnsValueWithDotNotation(): void
    {
        $this->createComposerJson([
            'extra' => [
                'linters' => [
                    'rector' => [
                        'paths' => ['/app', '/src'],
                    ],
                ],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);

        self::assertSame(['/app', '/src'], $loader->get('rector.paths'));
    }

    public function testGetAbsolutePathsReturnsAbsolutePaths(): void
    {
        $this->createComposerJson([
            'extra' => [
                'linters' => [
                    'rector' => [
                        'paths' => ['/app', '/src'],
                    ],
                ],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $paths = $loader->getAbsolutePaths('rector.paths');

        self::assertSame([
            $this->testDir . '/app',
            $this->testDir . '/src',
        ], $paths);
    }

    public function testGetAbsolutePathsReturnsEmptyArrayWhenKeyNotFound(): void
    {
        $this->createComposerJson([]);

        $loader = new ConfigurationLoader($this->testDir);
        $paths = $loader->getAbsolutePaths('nonexistent.paths');

        self::assertSame([], $paths);
    }

    public function testGetReturnsFalseValues(): void
    {
        $this->createComposerJson([
            'extra' => [
                'linters' => [
                    'phpstan' => [
                        'paths' => ['/src'],
                        'parallel' => false,
                    ],
                ],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);

        self::assertFalse($loader->get('phpstan.parallel', true));
    }

    public function testConstructorThrowsOnUnsupportedKeys(): void
    {
        $this->createComposerJson([
            'extra' => [
                'linters' => [
                    'phpstan' => [
                        'paths' => ['/src'],
                        'level' => 8,
                    ],
                ],
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported config key: extra.linters.phpstan.level');

        new ConfigurationLoader($this->testDir);
    }

    public function testConstructorUsesCustomExtraKey(): void
    {
        $this->createComposerJson([
            'extra' => [
                'custom-key' => [
                    'tool' => [
                        'value' => 'test',
                    ],
                ],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir, 'custom-key');

        self::assertSame('test', $loader->get('tool.value'));
    }

    private function createComposerJson(array $content): void
    {
        $json = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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
