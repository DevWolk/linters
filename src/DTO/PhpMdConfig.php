<?php

declare(strict_types=1);

namespace Linters\DTO;

use InvalidArgumentException;
use Linters\Utils\ConfigValidation;

final readonly class PhpMdConfig extends BaseToolConfig implements ToolConfigInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $paths = ConfigValidation::stringList($config['paths'] ?? []);

        if ($paths === []) {
            throw new InvalidArgumentException('Missing required config: extra.linters.phpmd.paths');
        }

        $skipDirs = ConfigValidation::optionalStringList($config['skip_dirs'] ?? null);
        $skipFiles = ConfigValidation::optionalStringList($config['skip_files'] ?? null);
        $baseline = $config['baseline'] ?? null;

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
