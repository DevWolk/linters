<?php

declare(strict_types=1);

namespace Linters\Enum;

enum ParallelConfigDefault
{
    case DISABLED;
    case ENABLED;

    public function isEnabled(): bool
    {
        return $this === self::ENABLED;
    }
}
