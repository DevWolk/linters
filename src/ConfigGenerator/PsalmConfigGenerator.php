<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

use DOMDocument;
use Linters\Utils\ConfigurationLoader;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use SimpleXMLElement;
use function file_exists;
use function getopt;
use function iterator_to_array;
use function realpath;
use function sprintf;
use function str_contains;
use function str_replace;
use const PHP_SAPI;
use const STDERR;
use const STDOUT;

/**
 * Generator for Psalm XML configuration files
 *
 * This class generates psalm.xml dynamically from:
 * - Base template (configs/psalm_default.xml)
 * - Paths from composer.json extra.linters.psalm.paths
 * - Skip patterns from extra.linters.psalm.skip
 * - Additional config from extra.linters.psalm.config
 */
class PsalmConfigGenerator
{
    protected ConfigurationLoader $loader;
    protected string $templatePath;

    public function __construct(?ConfigurationLoader $loader = null, ?string $templatePath = null)
    {
        $this->loader = $loader ?? new ConfigurationLoader();
        $this->templatePath = $templatePath ?? __DIR__ . '/../../configs/psalm_default.xml';
    }

    /**
     * Generate Psalm configuration and save to target file
     */
    public function generate(string $targetPath): void
    {
        $psalmConfigFile = file_get_contents($this->templatePath);
        $psalmConfig = new SimpleXMLElement($psalmConfigFile);

        $this->setUpPaths($psalmConfig);
        $this->setUpIgnore($psalmConfig);
        $this->setUpConfigs($psalmConfig);

        $xmlDocument = new DOMDocument('1.0');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->loadXML((string)$psalmConfig->asXML());

        $formatted = new SimpleXMLElement($xmlDocument->saveXML());
        $formatted->saveXML($targetPath);
    }

    protected function setUpPaths(SimpleXMLElement $psalmConfig): void
    {
        foreach ($this->loader->getAbsolutePaths('psalm.paths') as $path) {
            $psalmConfig
                ->projectFiles
                ->addChild('directory')
                ?->addAttribute('name', $path);
        }
    }

    protected function setUpIgnore(SimpleXMLElement $psalmConfig): void
    {
        foreach ($this->loader->getAbsolutePaths('psalm.skip') as $path) {
            $psalmConfig
                ->projectFiles
                ->ignoreFiles
                ->addChild('directory')
                ?->addAttribute('name', $path);
        }
    }

    protected function setUpConfigs(SimpleXMLElement $psalmConfig): void
    {
        $configs = $this->loader->get('psalm.config');

        if (empty($configs)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($configs),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $psalmConfig->registerXPathNamespace('x', 'https://getpsalm.org/schema/config');

        foreach ($iterator as $k => $v) {
            if (!$iterator->callHasChildren()) {
                $path = '/';
                for ($i = $iterator->getDepth() - 3; $i >= 0; $i--) {
                    $key = $iterator->getSubIterator($i)?->key();

                    if (!is_int($key)) {
                        if (str_contains($path, '//')) {
                            $path = str_replace('//', sprintf('//x:%s/', $key), $path);
                        } else {
                            $path .= sprintf('/x:%s', $key);
                        }
                    }
                }

                /** @var RecursiveArrayIterator $objectAttributes */
                $objectAttributes = $iterator->getSubIterator();
                $objectType = $iterator->getSubIterator($iterator->getDepth() - 2)?->key();
                $parent = $psalmConfig->xpath($path)[0];

                $hasElement = false;
                /** @var SimpleXMLElement $item */
                foreach ($parent->{$objectType} as $item) {
                    $attrMatch = 0;
                    foreach ($objectAttributes as $name => $value) {
                        if ((string)$item[$name] === $value) {
                            $attrMatch++;
                        }
                    }

                    if ($attrMatch === count(iterator_to_array($objectAttributes))) {
                        $hasElement = true;
                        break;
                    }
                }

                if ($hasElement === false) {
                    $elem = $parent->addChild($objectType);
                    if ($elem === null) {
                        continue;
                    }

                    foreach ($objectAttributes as $name => $value) {
                        $elem->addAttribute($name, $value);
                    }
                }
            }
        }
    }
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    (static function (): void {
        $autoloaderFound = false;
        $autoloaders = [
            __DIR__ . '/../../vendor/autoload.php',      // running from repository
            __DIR__ . '/../../../../autoload.php',       // running from installed package
        ];

        foreach ($autoloaders as $autoloader) {
            if (file_exists($autoloader)) {
                require_once $autoloader;
                $autoloaderFound = true;
                break;
            }
        }

        if ($autoloaderFound === false) {
            fwrite(STDERR, "[ERROR] Unable to locate Composer autoloader.\n");
            exit(1);
        }

        $options = getopt('t:c::', ['target:', 'config::']);
        $targetPath = $options['t'] ?? $options['target'] ?? null;

        if ($targetPath === null) {
            fwrite(STDERR, "Missing required option --target (-t).\n");
            exit(1);
        }

        $templatePath = $options['c'] ?? $options['config'] ?? null;
        $generator = new PsalmConfigGenerator(null, $templatePath ?: null);
        $generator->generate($targetPath);
        fwrite(STDOUT, sprintf("Psalm config generated at %s\n", $targetPath));
    })();
}
