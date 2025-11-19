<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

use DOMDocument;
use Linters\ConfigurationLoader;

/**
 * Generator for PHPMD XML ruleset files
 *
 * This class generates phpmd.ruleset.xml dynamically from:
 * - Base template or default configuration
 * - Paths from composer.json extra.linters.phpmd.paths
 * - Skip patterns from extra.linters.phpmd.skip
 * - Rulesets from extra.linters.phpmd.rulesets
 */
class PhpMdConfigGenerator
{
    protected ConfigurationLoader $loader;
    protected ?string $templatePath;

    /** Default PHPMD rulesets */
    protected array $defaultRulesets = [
        'cleancode',
        'codesize',
        'controversial',
        'design',
        'naming',
        'unusedcode',
    ];

    public function __construct(?ConfigurationLoader $loader = null, ?string $templatePath = null)
    {
        $this->loader = $loader ?? new ConfigurationLoader();
        $this->templatePath = $templatePath;
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
        if ($this->templatePath && file_exists($this->templatePath)) {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->load($this->templatePath);
        } else {
            $dom = $this->createDefaultConfiguration();
        }

        return $dom;
    }

    /**
     * Create default PHPMD configuration
     */
    protected function createDefaultConfiguration(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');

        $ruleset = $dom->createElement('ruleset');
        $ruleset->setAttribute('name', 'Project PHPMD Ruleset');
        $ruleset->setAttribute('xmlns', 'http://pmd.sf.net/ruleset/1.0.0');
        $ruleset->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $ruleset->setAttribute('xsi:schemaLocation', 'http://pmd.sf.net/ruleset/1.0.0 http://pmd.sf.net/ruleset_xml_schema.xsd');
        $ruleset->setAttribute('xsi:noNamespaceSchemaLocation', 'http://pmd.sf.net/ruleset_xml_schema.xsd');
        $dom->appendChild($ruleset);

        // Add description
        $description = $dom->createElement('description', 'PHPMD ruleset for project code quality checks');
        $ruleset->appendChild($description);

        // Get rulesets from configuration or use defaults
        $rulesets = $this->loader->get('phpmd.rulesets', $this->defaultRulesets);

        // Add each ruleset
        foreach ($rulesets as $rulesetName) {
            $this->addRuleset($dom, $ruleset, $rulesetName);
        }

        // Add exclude patterns
        $this->addExcludes($dom, $ruleset);

        return $dom;
    }

    /**
     * Add a ruleset to configuration
     */
    protected function addRuleset(DOMDocument $dom, \DOMElement $ruleset, string $rulesetName): void
    {
        $rule = $dom->createElement('rule');
        $rule->setAttribute('ref', "rulesets/{$rulesetName}.xml");
        $ruleset->appendChild($rule);

        // Add specific exclusions based on ruleset
        $this->addRulesetExclusions($dom, $rule, $rulesetName);
    }

    /**
     * Add specific exclusions for certain rulesets
     */
    protected function addRulesetExclusions(DOMDocument $dom, \DOMElement $rule, string $rulesetName): void
    {
        $exclusions = [
            'controversial' => [
                'Superglobals', // Often needed in frameworks
            ],
            'naming' => [
                'ShortVariable', // Too strict for common variables like $id, $key
                'LongVariable',  // Often unavoidable for descriptive names
            ],
            'codesize' => [
                'TooManyPublicMethods', // DTOs and services often need many methods
            ],
        ];

        if (!isset($exclusions[$rulesetName])) {
            return;
        }

        foreach ($exclusions[$rulesetName] as $exclusion) {
            $exclude = $dom->createElement('exclude');
            $exclude->setAttribute('name', $exclusion);
            $rule->appendChild($exclude);
        }
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
