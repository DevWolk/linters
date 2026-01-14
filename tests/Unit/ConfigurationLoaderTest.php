<?php

declare(strict_types=1);

namespace Linters\Tests\Unit;

use Linters\Enum\PhpVersion;
use Linters\Enum\RectorSet;
use Linters\Tests\TestCase;
use Linters\Utils\ConfigurationLoader;
use RuntimeException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\JsonException;

use function Safe\file_put_contents;
use function Safe\json_encode;

final class ConfigurationLoaderTest extends TestCase
{
    public function testConstructorThrowsExceptionWhenComposerJsonNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('composer.json file not found');

        new ConfigurationLoader('/nonexistent/path');
    }

    public function testGetRectorConfigReturnsPaths(): void
    {
        $this->createComposerJson([
            'rector' => [
                'paths'      => ['app', 'src'],
                'phpVersion' => '8.4',
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $config = $loader->getRectorConfig();

        self::assertSame(['app', 'src'], $config->paths);
    }

    public function testGetRectorConfigReturnsSetsFromFrameworks(): void
    {
        $this->createComposerJson([
            'rector' => [
                'paths'      => ['src'],
                'phpVersion' => '8.4',
                'sets'       => ['laravel11'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $config = $loader->getRectorConfig();

        self::assertCount(1, $config->sets);
        self::assertSame(RectorSet::LARAVEL11, $config->sets[0]);
    }

    public function testGetRectorConfigReturnsPhpVersion(): void
    {
        $this->createComposerJson([
            'rector' => [
                'paths'      => ['src'],
                'phpVersion' => '8.3',
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $config = $loader->getRectorConfig();

        self::assertSame(PhpVersion::PHP_83, $config->phpVersion);
    }

    public function testGetRectorConfigDefaultsToPhpVersion84(): void
    {
        $this->createComposerJson([
            'rector' => [
                'paths' => ['src'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $config = $loader->getRectorConfig();

        self::assertSame(PhpVersion::PHP_84, $config->phpVersion);
    }

    public function testGetPhpStanConfigReturnsParallelFalse(): void
    {
        $this->createComposerJson([
            'phpstan' => [
                'paths'    => ['src'],
                'parallel' => false,
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $config = $loader->getPhpStanConfig();

        self::assertFalse($config->parallel?->enabled);
    }

    public function testConfigIgnoresUnknownKeys(): void
    {
        $this->createComposerJson([
            'phpstan' => [
                'paths'       => ['src'],
                'unknown_key' => ['anything'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $config = $loader->getPhpStanConfig();

        self::assertSame(['src'], $config->paths);
    }

    /**
     * @throws JsonException
     * @throws FilesystemException
     */
    public function testConstructorUsesCustomExtraKey(): void
    {
        $json = json_encode([
            'extra' => [
                'custom-key' => [
                    'phpstan' => [
                        'paths' => ['src'],
                    ],
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents($this->testDir . '/composer.json', $json);

        $loader = new ConfigurationLoader($this->testDir, 'custom-key');
        $config = $loader->getPhpStanConfig();

        self::assertSame(['src'], $config->paths);
    }

    public function testGetComposerDirReturnsConfiguredDir(): void
    {
        $this->createComposerJson([]);

        $loader = new ConfigurationLoader($this->testDir);

        self::assertSame($this->testDir, $loader->getComposerDir());
    }
}
