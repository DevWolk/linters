<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\ConfigGenerator;

use InvalidArgumentException;
use Linters\ConfigGenerator\PhpCsConfigGenerator;
use Linters\Tests\TestCase;
use Linters\Utils\ConfigurationLoader;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\JsonException;

use function Safe\file_put_contents;
use function Safe\json_encode;

final class PhpCsConfigGeneratorTest extends TestCase
{
    private string $testDir;

    /**
     * @throws FilesystemException
     * @throws \DOMException
     * @throws DirException
     * @throws \JsonException
     */
    public function testGenerateAddsFilesAndExcludes(): void
    {
        $this->createComposerJson([
            'phpcs' => [
                'paths'     => ['src', 'tests'],
                'skip_dirs' => ['vendor'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpCsConfigGenerator($loader);
        $targetPath = $this->testDir . '/phpcs.xml';

        $generator->generate($targetPath);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->load($targetPath);

        $files = [];
        foreach ($dom->getElementsByTagName('file') as $node) {
            $files[] = $node->nodeValue;
        }

        $excludes = [];
        foreach ($dom->getElementsByTagName('exclude-pattern') as $node) {
            $excludes[] = $node->nodeValue;
        }

        self::assertContains('src', $files);
        self::assertContains('tests', $files);
        self::assertContains('vendor', $excludes);
    }

    /**
     * @throws FilesystemException
     * @throws \DOMException
     * @throws DirException
     * @throws \JsonException
     */
    public function testGenerateThrowsWhenPathsMissing(): void
    {
        $this->createComposerJson([
            'phpcs' => [
                'skip_dirs' => ['vendor'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpCsConfigGenerator($loader);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required config: extra.linters.phpcs.paths');

        $generator->generate($this->testDir . '/phpcs.xml');
    }

    /**
     * @throws FilesystemException
     * @throws \DOMException
     * @throws DirException
     * @throws \JsonException
     */
    public function testGenerateSetsCachePath(): void
    {
        $this->createComposerJson([
            'phpcs' => [
                'paths'     => ['src'],
                'cache_dir' => '.cache/phpcs',
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpCsConfigGenerator($loader);
        $targetPath = $this->testDir . '/phpcs.xml';

        $generator->generate($targetPath);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->load($targetPath);

        $cacheValue = null;
        foreach ($dom->getElementsByTagName('arg') as $arg) {
            if ($arg->getAttribute('name') === 'cache') {
                $cacheValue = $arg->getAttribute('value');
                break;
            }
        }

        self::assertSame('.cache/phpcs/.phpcs-cache', $cacheValue);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = $this->createTempDir('linters-phpcs-');
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
