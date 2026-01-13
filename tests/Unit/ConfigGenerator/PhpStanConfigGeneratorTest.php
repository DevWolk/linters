<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\ConfigGenerator;

use InvalidArgumentException;
use Linters\ConfigGenerator\PhpStanConfigGenerator;
use Linters\Tests\TestCase;
use Linters\Utils\ConfigurationLoader;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\JsonException;

use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\json_encode;

final class PhpStanConfigGeneratorTest extends TestCase
{
    private string $testDir;

    /**
     * @throws FilesystemException
     * @throws DirException
     * @throws \JsonException
     */
    public function testGenerateWritesConfigurationWithIncludesAndExcludes(): void
    {
        $this->createComposerJson([
            'phpstan' => [
                'paths'     => ['src', 'tests'],
                'skip_dirs' => ['vendor'],
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

    /**
     * @throws FilesystemException
     * @throws DirException
     * @throws \JsonException
     */
    public function testGenerateThrowsWhenPathsMissing(): void
    {
        $this->createComposerJson([
            'phpstan' => [
                'skip_dirs' => ['vendor'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpStanConfigGenerator($loader);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required config: extra.linters.phpstan.paths');

        $generator->generate($this->testDir . '/phpstan.neon');
    }

    /**
     * @throws FilesystemException
     * @throws DirException
     * @throws \JsonException
     */
    public function testGenerateAddsCacheDir(): void
    {
        $this->createComposerJson([
            'phpstan' => [
                'paths'     => ['src'],
                'cache_dir' => '.cache/phpstan',
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpStanConfigGenerator($loader);
        $targetPath = $this->testDir . '/phpstan.neon';

        $generator->generate($targetPath);

        $contents = file_get_contents($targetPath);
        self::assertStringContainsString('tmpDir: .cache/phpstan', $contents);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = $this->createTempDir('linters-phpstan-');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeDirectory($this->testDir);
    }

    /**
     * @param array<string, mixed> $lintersConfig
     *
     * @throws JsonException
     * @throws FilesystemException
     */
    private function createComposerJson(array $lintersConfig): void
    {
        $json = json_encode([
            'extra' => [
                'linters' => $lintersConfig,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents($this->testDir . '/composer.json', $json);
    }
}
