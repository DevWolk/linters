<?php

declare(strict_types=1);

namespace Linters\Tests\Unit\ConfigGenerator;

use DOMDocument;
use Linters\ConfigGenerator\PhpMdConfigGenerator;
use Linters\Tests\TestCase;
use Linters\Utils\ConfigurationLoader;

final class PhpMdConfigGeneratorTest extends TestCase
{
    public function testGenerateWritesConfiguration(): void
    {
        $this->createComposerJson([
            'phpmd' => [
                'paths'     => ['src'],
                'skip-dirs' => ['vendor'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpMdConfigGenerator($loader);
        $targetPath = $this->testDir . '/phpmd.ruleset.xml';

        $generator->generate($targetPath);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->load($targetPath);

        $excludes = [];

        foreach ($dom->getElementsByTagName('exclude-pattern') as $node) {
            $excludes[] = $node->nodeValue;
        }

        self::assertContains('vendor', $excludes);
    }
}
