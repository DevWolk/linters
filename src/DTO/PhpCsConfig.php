<?php

declare(strict_types=1);

namespace Linters\DTO;

use Linters\DTO\Contracts\AbstractToolConfig;
use Linters\DTO\Contracts\ToolConfigInterface;
use Linters\Utils\ConfigValidation;
use Linters\Utils\ParallelConfigOptions;

final readonly class PhpCsConfig extends AbstractToolConfig implements ToolConfigInterface
{
    public const string CACHE_NAME = '.phpcs-cache';

    /**
     * @param string[]                $paths
     * @param string[]                $skipDirs
     * @param string[]                $skipFiles
     * @param array<string, string[]> $ruleExcludes Rule name => array of exclude patterns
     */
    public function __construct(
        array $paths,
        array $skipDirs = [],
        array $skipFiles = [],
        ?ParallelConfigOptions $parallel = null,
        ?string $cacheDir = null,
        public array $ruleExcludes = [],
    ) {
        parent::__construct($paths, $skipDirs, $skipFiles, $parallel, $cacheDir);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $paths = ConfigValidation::requiredPaths($config['paths'] ?? [], 'phpcs');
        $skipDirs = ConfigValidation::optionalStringList($config['skip-dirs'] ?? null);
        $skipFiles = ConfigValidation::optionalStringList($config['skip-files'] ?? null);
        $parallel = ParallelConfigOptions::fromMixed($config['parallel'] ?? null);
        $cacheDir = $config['cache-dir'] ?? null;
        $ruleExcludes = self::parseRuleExcludes($config['rule-excludes'] ?? []);

        return new self(
            paths: $paths,
            skipDirs: $skipDirs,
            skipFiles: $skipFiles,
            parallel: $parallel,
            cacheDir: $cacheDir,
            ruleExcludes: $ruleExcludes,
        );
    }

    /**
     * @return array<string, string[]>
     */
    private static function parseRuleExcludes(mixed $ruleExcludes): array
    {
        if (!\is_array($ruleExcludes)) {
            return [];
        }

        $result = [];

        foreach ($ruleExcludes as $ruleName => $patterns) {
            if (!\is_string($ruleName)) {
                continue;
            }

            $result[$ruleName] = ConfigValidation::optionalStringList($patterns);
        }

        return $result;
    }
}
