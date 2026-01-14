<?php

declare(strict_types=1);

namespace Linters\DTO;

use Linters\Utils\ConfigValidation;

final readonly class PhpCsConfig extends BaseToolConfig implements ToolConfigInterface
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
        ?ParallelConfig $parallel = null,
        ?string $cacheDir = null,
        ?string $baseline = null,
        public array $ruleExcludes = [],
    ) {
        parent::__construct($paths, $skipDirs, $skipFiles, $parallel, $cacheDir, $baseline);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $paths = ConfigValidation::requiredPaths($config['paths'] ?? [], 'phpcs');
        $skipDirs = ConfigValidation::optionalStringList($config['skip_dirs'] ?? null);
        $skipFiles = ConfigValidation::optionalStringList($config['skip_files'] ?? null);
        $parallel = ParallelConfig::fromMixed($config['parallel'] ?? null);
        $cacheDir = $config['cache_dir'] ?? null;
        $ruleExcludes = self::parseRuleExcludes($config['rule_excludes'] ?? []);

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
