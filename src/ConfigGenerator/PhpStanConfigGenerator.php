<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

use Linters\Utils\ConfigurationLoader;

/**
 * Generator for PHPStan NEON configuration files
 *
 * This class generates phpstan.neon dynamically from:
 * - Base template
 * - Paths from composer.json extra.linters.phpstan.paths
 * - Skip patterns from extra.linters.phpstan.skip_dirs/skip_files
 * - Baseline from extra.linters.phpstan.baseline
 */
class PhpStanConfigGenerator implements ConfigGeneratorInterface
{
    private const PACKAGE_CONFIG_PATH = __DIR__ . '/../../configs/phpstan.neon';

    private const KEY_PARAMETERS = 'parameters';

    private const KEY_PATHS = 'paths';

    private const KEY_EXCLUDE_PATHS = 'excludePaths';

    private const KEY_TMP_DIR = 'tmpDir';

    private const KEY_INCLUDES = 'includes';

    protected ConfigurationLoader $loader;

    public function __construct(?ConfigurationLoader $loader = null)
    {
        $this->loader = $loader ?? new ConfigurationLoader();
    }

    public function generate(string $targetPath): void
    {
        $config = $this->buildConfiguration();
        $neonContent = $this->convertToNeon($config);

        file_put_contents($targetPath, $neonContent);
    }

    protected function buildConfiguration(): array
    {
        $config = $this->loader->getPhpStanConfig();

        $parameters = [
            self::KEY_PATHS => $config->paths,
        ];

        $excludePaths = array_merge($config->skipDirs, $config->skipFiles);
        if ($excludePaths !== []) {
            $parameters[self::KEY_EXCLUDE_PATHS] = $excludePaths;
        }

        $cacheDir = $config->cacheDir;
        if ($cacheDir !== null && $cacheDir !== '') {
            $parameters[self::KEY_TMP_DIR] = $cacheDir;
        }

        $includes = [self::PACKAGE_CONFIG_PATH];

        $baseline = $config->baseline;
        if ($baseline !== null && $baseline !== '') {
            $includes[] = $baseline;
        }

        return [
            self::KEY_PARAMETERS => $parameters,
            self::KEY_INCLUDES => $includes,
        ];
    }

    protected function convertToNeon(array $config, int $indent = 0): string
    {
        $neon = '';
        $indentStr = str_repeat('    ', $indent);

        foreach ($config as $key => $value) {
            if (is_array($value) === false) {
                $neon .= $indentStr . $key . ': ' . $this->formatValue($value) . "\n";
                continue;
            }

            $neon .= $indentStr . $key . ":\n";

            if (array_is_list($value)) {
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $neon .= $indentStr . "    -\n";
                        $neon .= $this->convertToNeon($item, $indent + 2);
                    } else {
                        $neon .= $indentStr . "    - " . $this->formatValue($item) . "\n";
                    }
                }
                continue;
            }

            $neon .= $this->convertToNeon($value, $indent + 1);
        }

        return $neon;
    }

    protected function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_string($value)) {
            // Quote strings that contain special characters or spaces
            if (preg_match('/[:\s#]/', $value)) {
                return "'" . str_replace("'", "''", $value) . "'";
            }

            return $value;
        }

        return (string)$value;
    }
}
