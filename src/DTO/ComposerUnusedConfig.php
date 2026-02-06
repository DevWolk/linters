<?php

declare(strict_types=1);

namespace Linters\DTO;

use Linters\DTO\Contracts\ToolConfigInterface;
use Linters\Utils\ConfigValidation;

final readonly class ComposerUnusedConfig implements ToolConfigInterface
{
    /**
     * @param string[] $namedFilters
     */
    public function __construct(public array $namedFilters = [])
    {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $filters = ConfigValidation::optionalStringList($config['named-filters'] ?? null);

        return new self(namedFilters: $filters);
    }
}
