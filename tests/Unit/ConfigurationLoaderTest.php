<?php

declare(strict_types=1);

namespace Linters\Tests\Unit;

use JsonException;
use Linters\Enum\RectorSet;
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
    public function testGetRectorConfigReturnsPaths(): void
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
        $config = $loader->getRectorConfig();

        self::assertSame(['app', 'src'], $config->paths);
    }

    /**
     * @throws JsonException
     * @throws FilesystemException
     * @throws DirException
     */
    public function testGetRectorConfigReturnsSetsFromFrameworks(): void
    {
        $this->createComposerJson([
            'extra' => [
                'linters' => [
                    'rector' => [
                        'paths'      => ['src'],
                        'frameworks' => ['laravel'],
                    ],
                ],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $config = $loader->getRectorConfig();

        self::assertCount(1, $config->sets);
        self::assertSame(RectorSet::LARAVEL11, $config->sets[0]);
    }

    /**
     * @throws JsonException
     * @throws FilesystemException
     * @throws DirException
     */
    public function testGetPhpStanConfigReturnsParallelFalse(): void
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
        $config = $loader->getPhpStanConfig();

        self::assertFalse($config->parallel?->enabled);
    }

    /**
     * @throws FilesystemException
     * @throws JsonException
     * @throws DirException
     */
    public function testConfigIgnoresUnknownKeys(): void
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
        $config = $loader->getPhpStanConfig();

        self::assertSame(['src'], $config->paths);
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
        $config = $loader->getPhpStanConfig();

        self::assertSame(['src'], $config->paths);
    }

    /**
     * @throws JsonException
     * @throws FilesystemException
     * @throws DirException
     */
    public function testGetComposerDirReturnsConfiguredDir(): void
    {
        $this->createComposerJson([]);

        $loader = new ConfigurationLoader($this->testDir);

        self::assertSame($this->testDir, $loader->getComposerDir());
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
