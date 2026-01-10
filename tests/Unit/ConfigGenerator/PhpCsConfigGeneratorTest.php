<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\ConfigGenerator;

use Linters\ConfigGenerator\PhpCsConfigGenerator;
use Linters\Utils\ConfigurationLoader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PhpCsConfigGeneratorTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = sys_get_temp_dir() . '/linters-phpcs-' . uniqid('', true);
        mkdir($this->testDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->testDir)) {
            $this->removeDirectory($this->testDir);
        }
    }

    public function testGenerateAddsFilesAndExcludes(): void
    {
        $this->createComposerJson([
            'phpcs' => [
                'paths' => ['/src', '/tests'],
                'skip' => ['/vendor'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpCsConfigGenerator($loader, $this->testDir . '/missing-template.xml');
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

        self::assertContains($this->testDir . '/src', $files);
        self::assertContains($this->testDir . '/tests', $files);
        self::assertContains($this->testDir . '/vendor', $excludes);
    }

    public function testGenerateThrowsWhenPathsMissing(): void
    {
        $this->createComposerJson([
            'phpcs' => [
                'skip' => ['/vendor'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpCsConfigGenerator($loader, $this->testDir . '/missing-template.xml');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required config: extra.linters.phpcs.paths');

        $generator->generate($this->testDir . '/phpcs.xml');
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
