<?php

declare(strict_types=1);

namespace Linters\DTO;

use InvalidArgumentException;

abstract readonly class BaseToolConfig
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
        public ?ParallelConfig $parallel = null,
        public ?string $cacheDir = null,
        public ?string $baseline = null,
    ) {
        if ($this->paths === []) {
            throw new InvalidArgumentException('paths is required');
        }
    }
}
