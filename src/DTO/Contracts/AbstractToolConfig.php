<?php

declare(strict_types=1);

namespace Linters\DTO\Contracts;

use Linters\Utils\ParallelConfigOptions;

abstract readonly class AbstractToolConfig
{
    /**
     * @param string[] $paths
     * @param string[] $skipDirs
     * @param string[] $skipFiles
     */
    public function __construct(
        public array $paths,
        public array $skipDirs = [],
        public array $skipFiles = [],
        public ?ParallelConfigOptions $parallel = null,
        public ?string $cacheDir = null,
        public ?string $baseline = null,
    ) {
    }
}
