<?php

declare(strict_types=1);

namespace Linters\DTO;

use Linters\Enum\PhpVersion;
use Linters\Enum\RectorSet;
use Linters\Utils\ImportNamesOptions;
use Linters\Utils\UnsafeOptions;

final readonly class RectorConfigOptions
{
    /**
     * @param RectorSet[] $sets
     */
    public function __construct(
        public PhpVersion $phpVersion,
        public array $sets = [],
        public ?string $memoryLimit = null,
        public bool $clearCache = true,
        public ImportNamesOptions $importNames = new ImportNamesOptions(),
        public UnsafeOptions $unsafe = new UnsafeOptions(),
    ) {
    }
}
