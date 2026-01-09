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
 * - Additional rules from extra.linters.phpcs.rules
 */
class PhpCsConfigGenerator
{
    protected ConfigurationLoader $loader;
    protected ?string $templatePath;

    public function __construct(?ConfigurationLoader $loader = null, ?string $templatePath = null)
    {
        $this->loader = $loader ?? new ConfigurationLoader();
        $this->templatePath = $templatePath ?? __DIR__ . '/../../configs/phpcs.xml';
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
        if ($this->templatePath && file_exists($this->templatePath)) {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->load($this->templatePath);
        } else {
            $dom = $this->createDefaultConfiguration();
        }

        // Add paths from configuration
        $this->addPaths($dom);

        // Add exclude patterns
        $this->addExcludes($dom);

        return $dom;
    }

    /**
     * Create default PHPCS configuration
     */
    protected function createDefaultConfiguration(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');

        $ruleset = $dom->createElement('ruleset');
        $ruleset->setAttribute('name', 'Project Coding Standard');
        $dom->appendChild($ruleset);

        // Add description
        $description = $dom->createElement('description', 'PHP CodeSniffer configuration for the project');
        $ruleset->appendChild($description);

        // Add PSR12 standard
        $rule = $dom->createElement('rule');
        $rule->setAttribute('ref', 'PSR12');
        $ruleset->appendChild($rule);

        // Configure Slevomat Coding Standard
        $config = $dom->createElement('config');
        $config->setAttribute('name', 'installed_paths');
        $config->setAttribute('value', '../../slevomat/coding-standard');
        $ruleset->appendChild($config);

        // Add some Slevomat rules
        $slevomatRules = [
            'SlevomatCodingStandard.TypeHints.DeclareStrictTypes',
            'SlevomatCodingStandard.TypeHints.ReturnTypeHintSpacing',
            'SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue',
            'SlevomatCodingStandard.TypeHints.ParameterTypeHintSpacing',
            'SlevomatCodingStandard.Namespaces.UnusedUses',
            'SlevomatCodingStandard.Operators.DisallowEqualOperators',
        ];

        foreach ($slevomatRules as $ruleName) {
            $rule = $dom->createElement('rule');
            $rule->setAttribute('ref', $ruleName);
            $ruleset->appendChild($rule);
        }

        // Add complexity rules
        $this->addComplexityRules($dom, $ruleset);

        return $dom;
    }

    /**
     * Add complexity rules to configuration
     */
    protected function addComplexityRules(DOMDocument $dom, \DOMElement $ruleset): void
    {
        // Cyclomatic Complexity
        $rule = $dom->createElement('rule');
        $rule->setAttribute('ref', 'Generic.Metrics.CyclomaticComplexity');
        $properties = $dom->createElement('properties');

        $property1 = $dom->createElement('property');
        $property1->setAttribute('name', 'complexity');
        $property1->setAttribute('value', '7');
        $properties->appendChild($property1);

        $property2 = $dom->createElement('property');
        $property2->setAttribute('name', 'absoluteComplexity');
        $property2->setAttribute('value', '14');
        $properties->appendChild($property2);

        $rule->appendChild($properties);
        $ruleset->appendChild($rule);

        // Nesting Level
        $rule = $dom->createElement('rule');
        $rule->setAttribute('ref', 'Generic.Metrics.NestingLevel');
        $properties = $dom->createElement('properties');

        $property = $dom->createElement('property');
        $property->setAttribute('name', 'nestingLevel');
        $property->setAttribute('value', '3');
        $properties->appendChild($property);

        $property = $dom->createElement('property');
        $property->setAttribute('name', 'absoluteNestingLevel');
        $property->setAttribute('value', '5');
        $properties->appendChild($property);

        $rule->appendChild($properties);
        $ruleset->appendChild($rule);
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
