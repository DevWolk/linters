<?php

declare(strict_types=1);

namespace Linters\DTO;

use InvalidArgumentException;
use Linters\Utils\ConfigValidation;

final readonly class PhpStanConfig extends BaseToolConfig implements ToolConfigInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $paths = ConfigValidation::stringList($config['paths'] ?? []);

        if ($paths === []) {
            throw new InvalidArgumentException('Missing required config: extra.linters.phpstan.paths');
        }

        $skipDirs = ConfigValidation::optionalStringList($config['skip_dirs'] ?? null);
        $skipFiles = ConfigValidation::optionalStringList($config['skip_files'] ?? null);
        $parallel = ParallelConfig::fromMixed($config['parallel'] ?? null);
        $cacheDir = $config['cache_dir'] ?? null;
        $baseline = $config['baseline'] ?? null;

        return new self(
            $paths,
            $skipDirs,
            $skipFiles,
            $parallel,
            $cacheDir,
            $baseline,
        );
    }
}
