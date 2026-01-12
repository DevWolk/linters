<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

use DOMDocument;
use Linters\Utils\ConfigurationLoader;

/**
 * Generator for PHP_CodeSniffer XML configuration files
 *
 * This class generates phpcs.xml dynamically from:
 * - Base template (configs/phpcs.xml) or default configuration
 * - Paths from composer.json extra.linters.phpcs.paths
 * - Skip patterns from extra.linters.phpcs.skip
 */
class PhpCsConfigGenerator
{
    protected ConfigurationLoader $loader;
    protected string $templatePath;

    public function __construct(?ConfigurationLoader $loader = null)
    {
        $this->loader = $loader ?? new ConfigurationLoader();
        $this->templatePath = __DIR__ . '/../../configs/phpcs.xml';
    }

    /**
     * Generate PHP_CodeSniffer configuration and save to target file
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

        // Add paths from configuration
        $this->addPaths($dom);

        // Add exclude patterns
        $this->addExcludes($dom);

        return $dom;
    }

    /**
     * Add file paths to configuration
     */
    protected function addPaths(DOMDocument $dom): void
    {
        $paths = $this->loader->getAbsolutePaths('phpcs.paths');
        if ($paths === []) {
            throw new \RuntimeException('Missing required config: extra.linters.phpcs.paths');
        }

        $ruleset = $dom->documentElement;

        foreach ($paths as $path) {
            $fileElement = $dom->createElement('file', $path);
            $ruleset->appendChild($fileElement);
        }
    }

    /**
     * Add exclude patterns to configuration
     */
    protected function addExcludes(DOMDocument $dom): void
    {
        $excludes = $this->loader->getAbsolutePaths('phpcs.skip', []);

        if (empty($excludes)) {
            return;
        }

        $ruleset = $dom->documentElement;

        foreach ($excludes as $exclude) {
            $excludeElement = $dom->createElement('exclude-pattern', $exclude);
            $ruleset->appendChild($excludeElement);
        }
    }
}
