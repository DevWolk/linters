<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

use Linters\DTO\ParallelConfig;
use Linters\DTO\PhpStanConfig;
use Linters\Utils\ConfigurationLoader;
use Safe\Exceptions\PcreException;
use Symfony\Component\Filesystem\Path;

use function Safe\file_put_contents;
use function Safe\preg_match;

/**
 * Generator for PHPStan NEON configuration files.
 *
 * This class generates phpstan.neon dynamically from:
 * - Base template
 * - Paths from composer.json extra.linters.phpstan.paths
 * - Skip patterns from extra.linters.phpstan.skip_dirs/skip_files
 * - Baseline from extra.linters.phpstan.baseline
 */
class PhpStanConfigGenerator implements ConfigGeneratorInterface
{
    private const string PACKAGE_CONFIG_PATH = __DIR__ . '/../../configs/phpstan.neon';

    private const string KEY_PARAMETERS = 'parameters';

    private const string KEY_PATHS = 'paths';

    private const string KEY_EXCLUDE_PATHS = 'excludePaths';

    private const string KEY_TMP_DIR = 'tmpDir';

    private const string KEY_INCLUDES = 'includes';

    private const string KEY_PARALLEL = 'parallel';

    public function __construct(protected ConfigurationLoader $loader = new ConfigurationLoader())
    {
    }

    public function generate(string $targetPath): void
    {
        $config = $this->buildConfiguration();
        $neonContent = $this->convertToNeon($config);

        file_put_contents($targetPath, $neonContent);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildConfiguration(): array
    {
        $config = $this->loader->getPhpStanConfig();

        $parameters = $this->buildParameters($config);
        $includes = $this->buildIncludes($config);

        return [
            self::KEY_PARAMETERS => $parameters,
            self::KEY_INCLUDES   => $includes,
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function convertToNeon(array $config, int $indent = 0): string
    {
        $neon = '';
        $indentStr = str_repeat('    ', $indent);

        foreach ($config as $key => $value) {
            $neon .= $this->convertKeyValue($key, $value, $indent, $indentStr);
        }

        return $neon;
    }

    /**
     * @throws PcreException
     */
    protected function formatValue(mixed $value): string
    {
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (\is_string($value)) {
            // Quote strings that contain special characters or spaces
            if (preg_match('/[:\s#]/', $value) === 1) {
                return "'" . str_replace("'", "''", $value) . "'";
            }

            return $value;
        }

        return (string)$value;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildParameters(PhpStanConfig $config): array
    {
        $parameters = [
            self::KEY_PATHS => $config->paths,
        ];

        $excludePaths = array_merge($config->skipDirs, $config->skipFiles);

        if ($excludePaths !== []) {
            $parameters[self::KEY_EXCLUDE_PATHS] = $excludePaths;
        }

        if ($config->cacheDir !== null && $config->cacheDir !== '') {
            $parameters[self::KEY_TMP_DIR] = $config->cacheDir;
        }

        $parallelConfig = $this->buildParallelConfig($config->parallel);

        if ($parallelConfig !== []) {
            $parameters[self::KEY_PARALLEL] = $parallelConfig;
        }

        return $parameters;
    }

    /**
     * @return array<string, int|float>
     */
    private function buildParallelConfig(?ParallelConfig $parallel): array
    {
        if ($parallel?->enabled !== true) {
            return [];
        }

        $config = [];

        if ($parallel?->maxProcesses !== null) {
            $config['maximumNumberOfProcesses'] = $parallel->maxProcesses;
        }

        if ($parallel?->timeout !== null) {
            $config['processTimeout'] = $parallel->timeout;
        }

        return $config;
    }

    /**
     * @return list<string>
     */
    private function buildIncludes(PhpStanConfig $config): array
    {
        $targetDir = $this->loader->getComposerDir();
        $packageConfigPath = Path::makeRelative(self::PACKAGE_CONFIG_PATH, $targetDir);

        $includes = [$packageConfigPath];

        if ($config->baseline !== null && $config->baseline !== '') {
            $includes[] = $config->baseline;
        }

        return $includes;
    }

    /**
     * @throws PcreException
     */
    private function convertKeyValue(string $key, mixed $value, int $indent, string $indentStr): string
    {
        if (\is_array($value) === false) {
            return $indentStr . $key . ': ' . $this->formatValue($value) . "\n";
        }

        $neon = $indentStr . $key . ":\n";

        if (array_is_list($value)) {
            return $neon . $this->convertList($value, $indent, $indentStr);
        }

        return $neon . $this->convertToNeon($value, $indent + 1);
    }

    /**
     * @param list<mixed> $items
     *
     * @throws PcreException
     */
    private function convertList(array $items, int $indent, string $indentStr): string
    {
        $neon = '';

        foreach ($items as $item) {
            $neon .= \is_array($item)
                ? $indentStr . "    -\n" . $this->convertToNeon($item, $indent + 2)
                : $indentStr . '    - ' . $this->formatValue($item) . "\n";
        }

        return $neon;
    }
}
