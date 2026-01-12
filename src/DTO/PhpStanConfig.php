<?php

declare(strict_types=1);

namespace Linters\DTO;

use InvalidArgumentException;
use Linters\Utils\ConfigValidation;

final readonly class PhpStanConfig extends BaseToolConfig implements ToolConfigInterface
{
    public static function fromArray(array $config): self
    {
        $paths = ConfigValidation::stringList($config['paths']);
        if ($paths === []) {
            throw new InvalidArgumentException('Missing required config: extra.linters.phpstan.paths');
        }

        $skipDirs = ConfigValidation::optionalStringList($config['skip_dirs'] ?? null, 'extra.linters.phpstan.skip_dirs');
        $skipFiles = ConfigValidation::optionalStringList($config['skip_files'] ?? null, 'extra.linters.phpstan.skip_files');
        $parallel = ParallelConfig::fromMixed($config['parallel'] ?? null);
        $cacheDir = ConfigValidation::optionalRelativePath($config['cache_dir'] ?? null, 'extra.linters.phpstan.cache_dir');
        $baseline = ConfigValidation::optionalRelativePath($config['baseline'] ?? null, 'extra.linters.phpstan.baseline');

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
