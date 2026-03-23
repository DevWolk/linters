<?php

declare(strict_types=1);

namespace Linters\Utils;

final readonly class UnsafeOptions
{
    public function __construct(public bool $treatClassesAsFinal = false)
    {
    }

    /**
     * @param array<string, mixed>|null $value
     */
    public static function fromMixed(?array $value): self
    {
        if ($value === null) {
            return new self();
        }

        return new self(
            treatClassesAsFinal: (bool) ($value['treat-classes-as-final'] ?? false),
        );
    }
}
