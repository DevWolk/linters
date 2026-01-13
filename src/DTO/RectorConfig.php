<?php

declare(strict_types=1);

namespace Linters\DTO;

use InvalidArgumentException;
use Linters\Utils\ConfigValidation;

final readonly class RectorConfig extends BaseToolConfig implements ToolConfigInterface
{
    /** @var string[] */
    public const FILE_EXTENSIONS = ['php'];

    /**
     * @param string[] $frameworks
     */
    public function __construct(
        array $paths,
        array $skipDirs = [],
        array $skipFiles = [],
        ?ParallelConfig $parallel = null,
        ?string $cacheDir = null,
        public array $frameworks = [],
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
        $frameworks = ConfigValidation::normalizeFrameworks($config['frameworks'] ?? null);

        return new self(
            $paths,
            $skipDirs,
            $skipFiles,
            $parallel,
            $cacheDir,
            $frameworks,
        );
    }

    public function isLaravelProject(): bool
    {
        return \in_array('laravel', $this->frameworks, true);
    }

    public function isSymfonyProject(): bool
    {
        return \in_array('symfony', $this->frameworks, true);
    }
}
