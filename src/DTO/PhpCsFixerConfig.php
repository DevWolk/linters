<?php

declare(strict_types=1);

namespace Linters\DTO;

use Linters\Utils\ConfigValidation;

final readonly class PhpCsFixerConfig extends BaseToolConfig implements ToolConfigInterface
{
    public const string PATTERN_NAME = '*.php';

    /** @var string[] */
    public const array NOT_NAMES = ['*.blade.php', '_*'];

    public const string CACHE_NAME = '.php-cs-fixer.cache';

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $paths = ConfigValidation::requiredPaths($config['paths'] ?? [], 'php-cs-fixer');
        $skipDirs = ConfigValidation::optionalStringList($config['skip_dirs'] ?? null);
        $skipFiles = ConfigValidation::optionalStringList($config['skip_files'] ?? null);
        $parallel = ParallelConfig::fromMixed($config['parallel'] ?? null);
        $cacheDir = $config['cache_dir'] ?? null;

        return new self(
            paths: $paths,
            skipDirs: $skipDirs,
            skipFiles: $skipFiles,
            parallel: $parallel,
            cacheDir: $cacheDir,
        );
    }
}
