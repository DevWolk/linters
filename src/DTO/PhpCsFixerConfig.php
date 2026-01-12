<?php

declare(strict_types=1);

namespace Linters\DTO;

use InvalidArgumentException;
use Linters\Utils\ConfigValidation;

final readonly class PhpCsFixerConfig extends BaseToolConfig implements ToolConfigInterface
{
    public const PATTERN_NAME = '*.php';

    /** @var string[] */
    public const NOT_NAMES = ['*.blade.php', '_*'];

    public const CACHE_NAME = '.php-cs-fixer.cache';

    public static function fromArray(array $config): self
    {
        $paths = ConfigValidation::stringList($config['paths']);
        if ($paths === []) {
            throw new InvalidArgumentException('Missing required config: extra.linters.php-cs-fixer.paths');
        }

        $skipDirs = ConfigValidation::optionalStringList($config['skip_dirs'] ?? null, 'extra.linters.php-cs-fixer.skip_dirs');
        $skipFiles = ConfigValidation::optionalStringList($config['skip_files'] ?? null, 'extra.linters.php-cs-fixer.skip_files');
        $parallel = ParallelConfig::fromMixed($config['parallel'] ?? null);
        $cacheDir = ConfigValidation::optionalRelativePath($config['cache_dir'] ?? null, 'extra.linters.php-cs-fixer.cache_dir');

        return new self(
            $paths,
            $skipDirs,
            $skipFiles,
            $parallel,
            $cacheDir,
            null,
        );
    }
}
