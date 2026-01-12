<?php

declare(strict_types=1);

namespace Linters\DTO;

use InvalidArgumentException;
use Linters\Utils\ConfigValidation;

final readonly class PhpMdConfig extends BaseToolConfig implements ToolConfigInterface
{
    public static function fromArray(array $config): self
    {
        $paths = ConfigValidation::stringList($config['paths']);
        if ($paths === []) {
            throw new InvalidArgumentException('Missing required config: extra.linters.phpmd.paths');
        }

        $skipDirs = ConfigValidation::optionalStringList($config['skip_dirs'] ?? null, 'extra.linters.phpmd.skip_dirs');
        $skipFiles = ConfigValidation::optionalStringList($config['skip_files'] ?? null, 'extra.linters.phpmd.skip_files');
        $baseline = ConfigValidation::optionalRelativePath($config['baseline'] ?? null, 'extra.linters.phpmd.baseline');

        return new self(
            $paths,
            $skipDirs,
            $skipFiles,
            null,
            null,
            $baseline,
        );
    }
}
