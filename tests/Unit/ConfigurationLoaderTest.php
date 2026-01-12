<?php

declare(strict_types=1);

namespace Linters\Tests\Unit;

use JsonException;
use Linters\Tests\TestCase;
use Linters\Utils\ConfigurationLoader;
use RuntimeException;

final class ConfigurationLoaderTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = $this->createTempDir('linters-test-');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeDirectory($this->testDir);
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
                        'paths' => ['app', 'src'],
                    ],
                ],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);

        self::assertSame(['app', 'src'], $loader->get('rector.paths'));
    }

    /**
     * @throws JsonException
     */
    public function testGetReturnsPathsAsConfigured(): void
    {
        $this->createComposerJson([
            'extra' => [
                'linters' => [
                    'rector' => [
                        'paths' => ['app', 'src'],
                    ],
                ],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $paths = $loader->get('rector.paths');

        self::assertSame([
            'app',
            'src',
        ], $paths);
    }

    /**
     * @throws JsonException
     */
    public function testGetReturnsDefaultArrayWhenKeyNotFound(): void
    {
        $this->createComposerJson([]);

        $loader = new ConfigurationLoader($this->testDir);
        $paths = $loader->get('nonexistent.paths', []);

        self::assertSame([], $paths);
    }

    /**
     * @throws JsonException
     */
    public function testGetReturnsPathsWithoutNormalization(): void
    {
        $this->createComposerJson([
            'extra' => [
                'linters' => [
                    'rector' => [
                        'paths' => ['src', 'tests'],
                    ],
                ],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $paths = $loader->get('rector.paths');

        self::assertSame([
            'src',
            'tests',
        ], $paths);
    }

    /**
     * @throws JsonException
     */
    public function testGetReturnsFalseValues(): void
    {
        $this->createComposerJson([
            'extra' => [
                'linters' => [
                    'phpstan' => [
                        'paths' => ['src'],
                        'parallel' => false,
                    ],
                ],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);

        self::assertFalse($loader->get('phpstan.parallel', true));
    }

    public function testConstructorRejectsUnsupportedTool(): void
    {
        $this->createComposerJson([
            'extra' => [
                'linters' => [
                    'unknown-tool' => [],
                ],
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported tool: extra.linters.unknown-tool');

        new ConfigurationLoader($this->testDir);
    }

    public function testConstructorIgnoresUnknownKeys(): void
    {
        $this->createComposerJson([
            'extra' => [
                'linters' => [
                    'phpstan' => [
                        'paths' => ['src'],
                        'unknown_key' => ['anything'],
                    ],
                ],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);

        self::assertSame(['src'], $loader->get('phpstan.paths'));
    }

    /**
     * @throws JsonException
     */
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

    /**
     * @throws JsonException
     */
    private function createComposerJson(array $content): void
    {
        $json = json_encode($content, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->testDir . '/composer.json', $json);
    }
}
