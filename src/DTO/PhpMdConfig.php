<?php

declare(strict_types=1);

namespace Linters\DTO;

use Linters\DTO\Contracts\AbstractToolConfig;
use Linters\DTO\Contracts\ToolConfigInterface;
use Linters\Utils\ConfigValidation;
use Linters\Utils\ParallelConfigOptions;

final readonly class PhpMdConfig extends AbstractToolConfig implements ToolConfigInterface
{
    public const string CACHE_FILE_NAME = 'phpmd.result-cache.php';

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $paths = ConfigValidation::requiredPaths($config['paths'] ?? [], 'phpmd');
        $skipDirs = ConfigValidation::optionalStringList($config['skip-dirs'] ?? null);
        $skipFiles = ConfigValidation::optionalStringList($config['skip-files'] ?? null);
        $parallel = ParallelConfigOptions::fromMixed($config['parallel'] ?? null);
        $cacheDir = $config['cache-dir'] ?? null;
        $baseline = $config['baseline'] ?? null;

        return new self(
            paths: $paths,
            skipDirs: $skipDirs,
            skipFiles: $skipFiles,
            parallel: $parallel,
            cacheDir: $cacheDir,
            baseline: $baseline,
        );
    }
}
