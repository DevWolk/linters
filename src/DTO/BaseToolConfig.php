<?php

declare(strict_types=1);

namespace Linters\DTO;

use InvalidArgumentException;

abstract readonly class BaseToolConfig
{
    /** @var string[] */
    public array $paths;

    /** @var string[] */
    public array $skipDirs;

    /** @var string[] */
    public array $skipFiles;

    public ?ParallelConfig $parallel;

    public ?string $cacheDir;

    public ?string $baseline;

    /**
     * @param string[] $paths
     * @param string[] $skipDirs
     * @param string[] $skipFiles
     */
    public function __construct(
        array $paths,
        array $skipDirs = [],
        array $skipFiles = [],
        ?ParallelConfig $parallel = null,
        ?string $cacheDir = null,
        ?string $baseline = null,
    ) {
        $this->paths = $paths;
        $this->skipDirs = $skipDirs;
        $this->skipFiles = $skipFiles;
        $this->parallel = $parallel;
        $this->cacheDir = $cacheDir;
        $this->baseline = $baseline;

        if ($this->paths === []) {
            throw new InvalidArgumentException('paths is required');
        }
    }
}
