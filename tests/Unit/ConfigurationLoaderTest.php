<?php

declare(strict_types=1);

namespace Linters\Tests\Unit;

use JsonException;
use Linters\Tests\TestCase;
use Linters\Utils\ConfigurationLoader;
use RuntimeException;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;

use function Safe\file_put_contents;
use function Safe\json_encode;

final class ConfigurationLoaderTest extends TestCase
{
    private string $testDir;

    public function testConstructorThrowsExceptionWhenComposerJsonNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('composer.json file not found');

        new ConfigurationLoader('/nonexistent/path');
    }

    /**
     * @throws JsonException
     * @throws FilesystemException
     * @throws DirException
     */
    public function testGetReturnsDefaultWhenKeyNotFound(): void
    {
        $this->createComposerJson([]);

        $loader = new ConfigurationLoader($this->testDir);

        self::assertSame('default', $loader->get('nonexistent.key', 'default'));
    }

    /**
     * @throws FilesystemException
     * @throws JsonException
     * @throws DirException
     */
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
     * @throws FilesystemException
     * @throws DirException
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
     * @throws FilesystemException
     * @throws DirException
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
     * @throws FilesystemException
     * @throws DirException
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
     * @throws FilesystemException
     * @throws DirException
     */
    public function testGetReturnsFalseValues(): void
    {
        $this->createComposerJson([
            'extra' => [
                'linters' => [
                    'phpstan' => [
                        'paths'    => ['src'],
                        'parallel' => false,
                    ],
                ],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);

        self::assertFalse($loader->get('phpstan.parallel', true));
    }

    /**
     * @throws FilesystemException
     * @throws JsonException
     * @throws DirException
     */
    public function testConstructorIgnoresUnknownKeys(): void
    {
        $this->createComposerJson([
            'extra' => [
                'linters' => [
                    'phpstan' => [
                        'paths'       => ['src'],
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
     * @throws FilesystemException
     * @throws DirException
     */
    public function testConstructorUsesCustomExtraKey(): void
    {
        $this->createComposerJson([
            'extra' => [
                'custom-key' => [
                    'phpstan' => [
                        'paths' => ['src'],
                    ],
                ],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir, 'custom-key');

        self::assertSame(['src'], $loader->get('phpstan.paths'));
    }

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

    /**
     * @param array<string, mixed> $content
     *
     * @throws JsonException
     * @throws FilesystemException
     */
    private function createComposerJson(array $content): void
    {
        $json = json_encode($content, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->testDir . '/composer.json', $json);
    }
}
