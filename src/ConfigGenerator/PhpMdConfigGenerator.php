<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

use DOMDocument;
use DOMElement;
use DOMException;
use Linters\DTO\PhpMdConfig;
use Linters\Utils\ConfigurationLoader;

/**
 * Generator for PHPMD XML ruleset files.
 *
 * This class generates phpmd.ruleset.xml dynamically from:
 * - Base template
 * - Paths from composer.json extra.linters.phpmd.paths
 * - Skip patterns from extra.linters.phpmd.skip_dirs/skip_files
 */
final readonly class PhpMdConfigGenerator implements ConfigGeneratorInterface
{
    private const string PACKAGE_CONFIG_PATH = __DIR__ . '/../../configs/phpmd.ruleset.xml';

    private const string TAG_EXCLUDE_PATTERN = 'exclude-pattern';

    public function __construct(private ConfigurationLoader $loader)
    {
    }

    /**
     * @throws DOMException
     */
    public function generate(string $targetPath): void
    {
        $dom = $this->buildConfiguration();

        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $dom->save($targetPath);
    }

    /**
     * @throws DOMException
     */
    private function buildConfiguration(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->load(self::PACKAGE_CONFIG_PATH);

        $ruleset = $dom->documentElement;

        if (!$ruleset instanceof DOMElement) {
            return $dom;
        }

        $config = $this->loader->getPhpMdConfig();
        $this->addExcludes($dom, $ruleset, $config);

        return $dom;
    }

    /**
     * @throws DOMException
     */
    private function addExcludes(
        DOMDocument $dom,
        DOMElement $ruleset,
        PhpMdConfig $config,
    ): void {
        $excludePatterns = array_merge(
            $config->skipDirs,
            $config->skipFiles,
        );

        foreach ($excludePatterns as $exclude) {
            $excludeElement = $dom->createElement(self::TAG_EXCLUDE_PATTERN, $exclude);
            $ruleset->appendChild($excludeElement);
        }
    }
}
