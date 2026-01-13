<?php

declare(strict_types=1);

namespace Linters\DTO;

use InvalidArgumentException;
use Linters\Enum\RectorSet;
use Linters\Utils\ConfigValidation;

final readonly class RectorConfig extends BaseToolConfig implements ToolConfigInterface
{
    /** @var string[] */
    public const array FILE_EXTENSIONS = ['php'];

    /**
     * @param RectorSet[] $sets
     */
    public function __construct(
        array $paths,
        array $skipDirs = [],
        array $skipFiles = [],
        ?ParallelConfig $parallel = null,
        ?string $cacheDir = null,
        public array $sets = [],
    ) {
        parent::__construct($paths, $skipDirs, $skipFiles, $parallel, $cacheDir);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $paths = ConfigValidation::stringList($config['paths'] ?? []);

        if ($paths === []) {
            throw new InvalidArgumentException('Missing required config: extra.linters.rector.paths');
        }

        $skipDirs = ConfigValidation::optionalStringList($config['skip_dirs'] ?? null);
        $skipFiles = ConfigValidation::optionalStringList($config['skip_files'] ?? null);
        $parallel = ParallelConfig::fromMixed($config['parallel'] ?? null, true);
        $cacheDir = $config['cache_dir'] ?? null;
        $sets = ConfigValidation::normalizeSets($config['sets'] ?? $config['frameworks'] ?? null);

        return new self(
            $paths,
            $skipDirs,
            $skipFiles,
            $parallel,
            $cacheDir,
            $sets,
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
