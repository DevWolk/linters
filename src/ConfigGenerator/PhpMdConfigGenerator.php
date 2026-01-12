<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

use DOMDocument;
use Linters\Utils\ConfigurationLoader;

/**
 * Generator for PHPMD XML ruleset files
 *
 * This class generates phpmd.ruleset.xml dynamically from:
 * - Base template or default configuration
 * - Skip patterns from extra.linters.phpmd.skip
 */
class PhpMdConfigGenerator
{
    protected ConfigurationLoader $loader;
    protected string $templatePath;

    public function __construct(?ConfigurationLoader $loader = null)
    {
        $this->loader = $loader ?? new ConfigurationLoader();
        $this->templatePath = __DIR__ . '/../../configs/phpmd.ruleset.xml';
    }

    /**
     * Generate PHPMD configuration and save to target file
     */
    public function generate(string $targetPath): void
    {
        $dom = $this->buildConfiguration();

        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $dom->save($targetPath);
    }

    /**
     * Build configuration DOM from loader settings
     */
    protected function buildConfiguration(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->load($this->templatePath);

        $ruleset = $dom->documentElement;
        if ($ruleset instanceof \DOMElement) {
            $this->addExcludes($dom, $ruleset);
        }

        return $dom;
    }

    /**
     * Add exclude patterns to configuration
     */
    protected function addExcludes(DOMDocument $dom, \DOMElement $ruleset): void
    {
        $excludes = $this->loader->getAbsolutePaths('phpmd.skip', []);

        if (empty($excludes)) {
            return;
        }

        foreach ($excludes as $exclude) {
            $excludeElement = $dom->createElement('exclude-pattern', $exclude);
            $ruleset->appendChild($excludeElement);
        }
    }
}
