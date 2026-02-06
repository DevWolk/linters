<?php

declare(strict_types=1);

namespace Linters\DTO\Contracts;

interface ToolConfigInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self;
}
