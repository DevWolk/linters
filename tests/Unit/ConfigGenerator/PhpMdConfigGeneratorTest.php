<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\ConfigGenerator;

use Linters\ConfigGenerator\PhpMdConfigGenerator;
use Linters\Utils\ConfigurationLoader;
use PHPUnit\Framework\TestCase;

final class PhpMdConfigGeneratorTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = sys_get_temp_dir() . '/linters-phpmd-' . uniqid('', true);
        mkdir($this->testDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->testDir)) {
            $this->removeDirectory($this->testDir);
        }
    }

    public function testGenerateAddsExcludes(): void
    {
        $this->createComposerJson([
            'phpmd' => [
                'skip' => ['/vendor'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpMdConfigGenerator($loader);
        $targetPath = $this->testDir . '/phpmd.ruleset.xml';

        $generator->generate($targetPath);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->load($targetPath);

        $excludes = [];
        foreach ($dom->getElementsByTagName('exclude-pattern') as $node) {
            $excludes[] = $node->nodeValue;
        }

        self::assertContains($this->testDir . '/vendor', $excludes);
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
