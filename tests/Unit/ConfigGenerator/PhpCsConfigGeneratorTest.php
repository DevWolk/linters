<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\ConfigGenerator;

use DOMDocument;
use Linters\ConfigGenerator\PhpCsConfigGenerator;
use Linters\Tests\TestCase;
use Linters\Utils\ConfigurationLoader;

final class PhpCsConfigGeneratorTest extends TestCase
{
    public function testGenerateWritesConfiguration(): void
    {
        $this->createComposerJson([
            'phpcs' => [
                'paths' => ['src', 'tests'],
                'skip-dirs' => ['vendor'],
                'cache-dir' => '.cache/phpcs',
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpCsConfigGenerator($loader);
        $targetPath = $this->testDir . '/phpcs.xml';

        $generator->generate($targetPath);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->load($targetPath);

        // paths
        $files = $this->getNodeValues($dom, 'file');
        self::assertContains('src', $files);
        self::assertContains('tests', $files);

        // excludes
        $excludes = $this->getNodeValues($dom, 'exclude-pattern');
        self::assertContains('vendor', $excludes);

        // cache
        $cacheValue = $this->getArgValue($dom, 'cache');
        self::assertSame('.cache/phpcs/.phpcs-cache', $cacheValue);
    }

    /**
     * @return list<string>
     */
    private function getNodeValues(DOMDocument $dom, string $tagName): array
    {
        $values = [];

        foreach ($dom->getElementsByTagName($tagName) as $node) {
            $values[] = (string) $node->nodeValue;
        }

        return $values;
    }

    private function getArgValue(DOMDocument $dom, string $name): ?string
    {
        foreach ($dom->getElementsByTagName('arg') as $arg) {
            if ($arg->getAttribute('name') === $name) {
                return $arg->getAttribute('value');
            }
        }

        return null;
    }
}
