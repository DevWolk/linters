<?php

declare(strict_types=1);

namespace Linters\DTO;

use Linters\DTO\Contracts\AbstractToolConfig;
use Linters\DTO\Contracts\ToolConfigInterface;
use Linters\Utils\ConfigValidation;
use Linters\Utils\ParallelConfigOptions;

final readonly class PhpCsFixerConfig extends AbstractToolConfig implements ToolConfigInterface
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
        $skipDirs = ConfigValidation::optionalStringList($config['skip-dirs'] ?? null);
        $skipFiles = ConfigValidation::optionalStringList($config['skip-files'] ?? null);
        $parallel = ParallelConfigOptions::fromMixed($config['parallel'] ?? null);
        $cacheDir = $config['cache-dir'] ?? null;

        return new self(
            paths: $paths,
            skipDirs: $skipDirs,
            skipFiles: $skipFiles,
            parallel: $parallel,
            cacheDir: $cacheDir,
        );
    }
}
