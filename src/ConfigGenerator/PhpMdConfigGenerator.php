<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

use DOMDocument;
use DOMElement;
use DOMException;
use Linters\ConfigGenerator\Contracts\ConfigGeneratorInterface;
use Linters\DTO\PhpMdConfig;
use Linters\Utils\ConfigurationLoader;
use Linters\Utils\ConfigValidation;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Generator for PHPMD XML ruleset files.
 *
 * This class generates phpmd.ruleset.xml dynamically from:
 * - Base template
 * - Paths from composer.json extra.linters.phpmd.paths
 * - Skip patterns from extra.linters.phpmd.skip-dirs/skip-files
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
        $config = $this->loader->getPhpMdConfig();
        $this->createCacheDirectory($config);

        $dom = $this->buildConfiguration($config);

        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if ($dom->save($targetPath) === false) {
            throw new \RuntimeException('Failed to write PHPMD config to: ' . $targetPath);
        }
    }

    /**
     * @throws DOMException
     */
    private function buildConfiguration(PhpMdConfig $config): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');

        if ($dom->load(self::PACKAGE_CONFIG_PATH) === false) {
            throw new \RuntimeException('Failed to load PHPMD template: ' . self::PACKAGE_CONFIG_PATH);
        }

        $ruleset = $dom->documentElement;

        if (!$ruleset instanceof DOMElement) {
            throw new \RuntimeException('Invalid PHPMD base template: missing root element');
        }

        $this->addExcludes($dom, $ruleset, $config);

        return $dom;
    }

    private function createCacheDirectory(PhpMdConfig $config): void
    {
        if (!ConfigValidation::isNonEmptyString($config->cacheDir)) {
            return;
        }

        $cacheDirectory = Path::makeAbsolute($config->cacheDir, $this->loader->getComposerDir());
        new Filesystem()->mkdir($cacheDirectory);
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
