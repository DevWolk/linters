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
 * - Skip patterns from extra.linters.phpstan.skip
 * - Baseline from extra.linters.phpstan.baseline
 */
class PhpStanConfigGenerator
{
    protected ConfigurationLoader $loader;
    protected string $templatePath;

    public function __construct(?ConfigurationLoader $loader = null)
    {
        $this->loader = $loader ?? new ConfigurationLoader();
        $this->templatePath = __DIR__ . '/../../configs/phpstan.neon';
    }

    /**
     * Generate PHPStan configuration and save to target file
     */
    public function generate(string $targetPath): void
    {
        $config = $this->buildConfiguration();
        $neonContent = $this->convertToNeon($config);

        file_put_contents($targetPath, $neonContent);
    }

    /**
     * Build configuration array from loader settings
     */
    protected function buildConfiguration(): array
    {
        $paths = $this->loader->getAbsolutePaths('phpstan.paths');
        if ($paths === []) {
            throw new \RuntimeException('Missing required config: extra.linters.phpstan.paths');
        }

        $config = [];

        // Add exclude paths if specified
        $excludePaths = $this->loader->getAbsolutePaths('phpstan.skip', []);
        $parameters = [
            'paths' => $this->getRelativePaths($paths),
        ];
        if ($excludePaths !== []) {
            $parameters['excludePaths'] = $this->getRelativePaths($excludePaths);
        }

        $config['parameters'] = $parameters;
        $includes[] = $this->templatePath;

        $baseline = $this->loader->get('phpstan.baseline');
        if ($baseline) {
            $includes[] = (string)$baseline;
        }

        $config['includes'] = $includes;

        return $config;
    }

    /**
     * Convert absolute paths to paths relative to project root when possible
     */
    protected function getRelativePaths(array $absolutePaths): array
    {
        $rootDir = rtrim($this->loader->getComposerDir(), '/');

        return array_map(
            static function (string $path) use ($rootDir): string {
                if ($rootDir !== '' && str_starts_with($path, $rootDir . '/')) {
                    return substr($path, strlen($rootDir) + 1);
                }

                return $path;
            },
            $absolutePaths
        );
    }

    /**
     * Convert configuration array to NEON format
     */
    protected function convertToNeon(array $config, int $indent = 0): string
    {
        $neon = '';
        $indentStr = str_repeat('    ', $indent);

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                // Check if array is associative or indexed
                if ($this->isAssociativeArray($value)) {
                    $neon .= $indentStr . $key . ":\n";
                    $neon .= $this->convertToNeon($value, $indent + 1);
                } else {
                    $neon .= $indentStr . $key . ":\n";
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $neon .= $indentStr . "    -\n";
                            $neon .= $this->convertToNeon($item, $indent + 2);
                        } else {
                            $neon .= $indentStr . "    - " . $this->formatValue($item) . "\n";
                        }
                    }
                }
            } else {
                $neon .= $indentStr . $key . ': ' . $this->formatValue($value) . "\n";
            }
        }

        return $neon;
    }

    /**
     * Check if array is associative
     */
    protected function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Format value for NEON output
     */
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
