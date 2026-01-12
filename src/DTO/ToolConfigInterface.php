<?php

declare(strict_types=1);

namespace Linters\DTO;

interface ToolConfigInterface
{
    public static function fromArray(array $config): self;
}
