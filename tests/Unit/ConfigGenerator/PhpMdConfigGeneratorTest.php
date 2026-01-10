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

    public function testGenerateUsesCustomRulesetsAndExcludes(): void
    {
        $this->createComposerJson([
            'phpmd' => [
                'rulesets' => ['codesize'],
                'skip' => ['/vendor'],
            ],
        ]);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpMdConfigGenerator($loader, $this->testDir . '/missing-template.xml');
        $targetPath = $this->testDir . '/phpmd.ruleset.xml';

        $generator->generate($targetPath);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->load($targetPath);

        $rules = $dom->getElementsByTagName('rule');
        $ruleRefs = [];
        foreach ($rules as $rule) {
            $ruleRefs[] = $rule->getAttribute('ref');
        }

        $excludes = [];
        foreach ($dom->getElementsByTagName('exclude-pattern') as $node) {
            $excludes[] = $node->nodeValue;
        }

        self::assertContains('rulesets/codesize.xml', $ruleRefs);
        self::assertContains($this->testDir . '/vendor', $excludes);
    }

    public function testGenerateAppliesRulesetsWhenTemplateProvided(): void
    {
        $this->createComposerJson([
            'phpmd' => [
                'rulesets' => ['cleancode', 'unusedcode'],
                'skip' => ['/vendor'],
            ],
        ]);

        $templatePath = $this->testDir . '/template.xml';
        file_put_contents($templatePath, <<<'XML'
<?xml version="1.0"?>
<ruleset name="Template Rules"
         xmlns="http://pmd.sf.net/ruleset/1.0.0"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://pmd.sf.net/ruleset/1.0.0 http://pmd.sf.net/ruleset_xml_schema.xsd">
    <description>Template</description>
    <rule ref="rulesets/cleancode.xml">
        <exclude name="StaticAccess"/>
    </rule>
    <rule ref="rulesets/design.xml/DepthOfInheritance"/>
</ruleset>
XML);

        $loader = new ConfigurationLoader($this->testDir);
        $generator = new PhpMdConfigGenerator($loader, $templatePath);
        $targetPath = $this->testDir . '/phpmd.ruleset.xml';

        $generator->generate($targetPath);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->load($targetPath);

        $ruleRefs = [];
        foreach ($dom->getElementsByTagName('rule') as $rule) {
            $ruleRefs[] = $rule->getAttribute('ref');
        }

        self::assertContains('rulesets/cleancode.xml', $ruleRefs);
        self::assertContains('rulesets/unusedcode.xml', $ruleRefs);
        self::assertNotContains('rulesets/design.xml/DepthOfInheritance', $ruleRefs);

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
