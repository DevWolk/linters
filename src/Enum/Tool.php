<?php

declare(strict_types=1);

namespace Linters\Enum;

use InvalidArgumentException;

enum Tool: string
{
    case PHP_STAN = 'phpstan';
    case PHP_CS = 'phpcs';
    case PHP_MD = 'phpmd';
    case RECTOR = 'rector';
    case PHP_CS_FIXER = 'php-cs-fixer';
    case COMPOSER_UNUSED = 'composer-unused';

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
            self::RECTOR => 'Rector',
            self::PHP_CS_FIXER => 'PHP-CS-Fixer',
            self::COMPOSER_UNUSED => 'composer-unused',
        };
    }

    public function binary(): string
    {
        return $this->value;
    }

    public function requiresGeneration(): bool
    {
        return match ($this) {
            self::PHP_STAN,
            self::PHP_CS,
            self::PHP_MD => true,
            default => false,
        };
    }

    public function generatedTarget(): ?string
    {
        return match ($this) {
            self::PHP_STAN => 'phpstan.neon',
            self::PHP_CS => 'phpcs.xml',
            self::PHP_MD => 'phpmd.ruleset.xml',
            default => null,
        };
    }

    public function packageConfigPath(): ?string
    {
        return match ($this) {
            self::RECTOR => 'configs/rector.php',
            self::PHP_CS_FIXER => 'configs/.php-cs-fixer.dist.php',
            self::COMPOSER_UNUSED => 'configs/composer-unused.php',
            default => null,
        };
    }
}
