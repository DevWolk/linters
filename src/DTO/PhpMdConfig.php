<?php

declare(strict_types=1);

namespace Linters\DTO;

use Linters\DTO\Contracts\AbstractToolConfig;
use Linters\DTO\Contracts\ToolConfigInterface;
use Linters\Utils\ConfigValidation;

final readonly class PhpMdConfig extends AbstractToolConfig implements ToolConfigInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $paths = ConfigValidation::requiredPaths($config['paths'] ?? [], 'phpmd');
        $skipDirs = ConfigValidation::optionalStringList($config['skip-dirs'] ?? null);
        $skipFiles = ConfigValidation::optionalStringList($config['skip-files'] ?? null);
        $baseline = $config['baseline'] ?? null;

        return new self(
            paths: $paths,
            skipDirs: $skipDirs,
            skipFiles: $skipFiles,
            baseline: $baseline,
        );
    }
}
