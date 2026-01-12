<?php

declare(strict_types=1);

namespace Linters\DTO;

use Linters\Utils\ConfigValidation;

final readonly class ComposerUnusedConfig implements ToolConfigInterface
{
    /** @var string[] */
    public array $namedFilters;

    /**
     * @param string[] $namedFilters
     */
    public function __construct(array $namedFilters = [])
    {
        $this->namedFilters = $namedFilters;
    }

    public static function fromArray(array $config): self
    {
        $filters = ConfigValidation::optionalStringList($config['named-filters'] ?? null, 'extra.linters.composer-unused.named-filters');

        return new self($filters);
    }
}
