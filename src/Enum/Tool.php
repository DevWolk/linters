<?php

declare(strict_types=1);

namespace Linters\Enum;

use InvalidArgumentException;

enum Tool: string
{
    case PHP_STAN = 'phpstan';
    case PHP_CS = 'phpcs';
    case PHP_MD = 'phpmd';

    public static function fromName(string $name): self
    {
        $tool = self::tryFrom($name);
        if ($tool === null) {
            throw new InvalidArgumentException("Unknown tool: {$name}");
        }

        return $tool;
    }

    public function label(): string
    {
        return match ($this) {
            self::PHP_STAN => 'PHPStan',
            self::PHP_CS => 'PHPCS',
            self::PHP_MD => 'PHPMD',
        };
    }

    public function binary(): string
    {
        return $this->value;
    }

    public function targetKey(): string
    {
        return $this->value . '.target';
    }

    public function templateKey(): string
    {
        return $this->value . '.template';
    }

    public function formatKey(): ?string
    {
        return $this === self::PHP_MD ? 'phpmd.format' : null;
    }

    public function requiresGeneration(): bool
    {
        return true;
    }
}
