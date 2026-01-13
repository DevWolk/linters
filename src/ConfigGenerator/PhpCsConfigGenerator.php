<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

use DOMDocument;
use DOMException;
use Linters\DTO\PhpCsConfig;
use Linters\Utils\ConfigurationLoader;

/**
 * Generator for PHP_CodeSniffer XML configuration files.
 *
 * This class generates phpcs.xml dynamically from:
 * - Base template (configs/phpcs.xml)
 * - Paths from composer.json extra.linters.phpcs.paths
 * - Skip patterns from extra.linters.phpcs.skip_dirs/skip_files
 */
class PhpCsConfigGenerator implements ConfigGeneratorInterface
{
    private const string PACKAGE_CONFIG_PATH = __DIR__ . '/../../configs/phpcs.xml';

    private const string TAG_FILE = 'file';

    private const string TAG_EXCLUDE_PATTERN = 'exclude-pattern';

    private const string TAG_ARG = 'arg';

    private const string CACHE_ARG_NAME = 'cache';

    private const string ATTR_NAME = 'name';

    private const string ATTR_VALUE = 'value';

    public function __construct(protected ConfigurationLoader $loader = new ConfigurationLoader())
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
    protected function buildConfiguration(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->load(self::PACKAGE_CONFIG_PATH);

        $config = $this->loader->getPhpCsConfig();
        $ruleset = $dom->documentElement;

        foreach ($config->paths as $path) {
            $fileElement = $dom->createElement(self::TAG_FILE, $path);
            $ruleset?->appendChild($fileElement);
        }

        $excludePatterns = array_merge(
            $config->skipDirs,
            $config->skipFiles,
        );

        foreach ($excludePatterns as $exclude) {
            $excludeElement = $dom->createElement(self::TAG_EXCLUDE_PATTERN, $exclude);
            $ruleset?->appendChild($excludeElement);
        }

        $cacheDir = $config->cacheDir;

        if ($cacheDir !== null && $cacheDir !== '') {
            $cachePath = \sprintf('%s/%s', rtrim($cacheDir, '/'), PhpCsConfig::CACHE_NAME);
            $this->setCachePath($dom, $cachePath);
        }

        return $dom;
    }

    /**
     * @throws DOMException
     */
    private function setCachePath(DOMDocument $dom, string $cachePath): void
    {
        foreach ($dom->getElementsByTagName(self::TAG_ARG) as $arg) {
            if ($arg->getAttribute(self::ATTR_NAME) === self::CACHE_ARG_NAME) {
                $arg->setAttribute(self::ATTR_VALUE, $cachePath);

                return;
            }
        }

        $ruleset = $dom->documentElement;

        if ($ruleset === null) {
            return;
        }

        $cacheElement = $dom->createElement(self::TAG_ARG);
        $cacheElement->setAttribute(self::ATTR_NAME, self::CACHE_ARG_NAME);
        $cacheElement->setAttribute(self::ATTR_VALUE, $cachePath);

        $ruleset->appendChild($cacheElement);
    }
}
