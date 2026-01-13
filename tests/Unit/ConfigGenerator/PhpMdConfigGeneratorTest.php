<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\ConfigGenerator;

use Linters\ConfigGenerator\PhpMdConfigGenerator;
use Linters\Tests\TestCase;
use Linters\Utils\ConfigurationLoader;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\JsonException;

use function Safe\file_put_contents;
use function Safe\json_encode;

final class PhpMdConfigGeneratorTest extends TestCase
{
    private string $testDir;

    /**
     * @throws FilesystemException
     * @throws \DOMException
     * @throws DirException
     * @throws \JsonException
     * @throws JsonException
     */
    public function testGenerateAddsExcludes(): void
    {
        $this->createComposerJson([
            'phpmd' => [
                'paths'     => ['src'],
                'skip_dirs' => ['vendor'],
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

        self::assertContains('vendor', $excludes);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = $this->createTempDir('linters-phpmd-');
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
