<?php

declare(strict_types=1);

namespace Linters\DTO;

use Linters\DTO\Contracts\AbstractToolConfig;
use Linters\DTO\Contracts\ToolConfigInterface;
use Linters\Enum\PhpVersion;
use Linters\Enum\RectorSet;
use Linters\Utils\ConfigValidation;
use Linters\Utils\ImportNamesOptions;
use Linters\Utils\ParallelConfigOptions;
use Linters\Utils\UnsafeOptions;

final readonly class RectorConfig extends AbstractToolConfig implements ToolConfigInterface
{
    /** @var string[] */
    public const array FILE_EXTENSIONS = ['php'];

    /**
     * @param RectorSet[] $sets
     */
    public function __construct(
        array $paths,
        array $skipDirs,
        array $skipFiles,
        ?ParallelConfigOptions $parallel,
        ?string $cacheDir,
        public PhpVersion $phpVersion,
        public array $sets = [],
        public ?string $memoryLimit = null,
        public bool $clearCache = true,
        public ImportNamesOptions $importNames = new ImportNamesOptions(),
        public UnsafeOptions $unsafe = new UnsafeOptions(),
    ) {
        parent::__construct($paths, $skipDirs, $skipFiles, $parallel, $cacheDir);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $paths = ConfigValidation::requiredPaths($config['paths'] ?? [], 'rector');
        $skipDirs = ConfigValidation::optionalStringList($config['skip-dirs'] ?? null);
        $skipFiles = ConfigValidation::optionalStringList($config['skip-files'] ?? null);
        $parallel = ParallelConfigOptions::fromMixed($config['parallel'] ?? null, true);
        $cacheDir = $config['cache-dir'] ?? null;
        $phpVersion = PhpVersion::from($config['php-version'] ?? PhpVersion::PHP_84->value);
        $sets = ConfigValidation::normalizeSets($config['sets'] ?? null);
        $memoryLimit = $config['memory-limit'] ?? null;
        $clearCache = $config['clear-cache'] ?? true;
        $importNames = ImportNamesOptions::fromMixed($config['import-names'] ?? null);
        $unsafe = UnsafeOptions::fromMixed($config['unsafe'] ?? null);

        return new self(
            paths: $paths,
            skipDirs: $skipDirs,
            skipFiles: $skipFiles,
            parallel: $parallel,
            cacheDir: $cacheDir,
            phpVersion: $phpVersion,
            sets: $sets,
            memoryLimit: $memoryLimit,
            clearCache: $clearCache,
            importNames: $importNames,
            unsafe: $unsafe,
        );
    }

    public function isLaravelProject(): bool
    {
        return \in_array(RectorSet::LARAVEL11, $this->sets, true) ||
            \in_array(RectorSet::LARAVEL12, $this->sets, true);
    }

    public function isSymfonyProject(): bool
    {
        return \in_array(RectorSet::SYMFONY, $this->sets, true);
    }
}
