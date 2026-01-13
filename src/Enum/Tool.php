<?php

declare(strict_types=1);

namespace Linters\Enum;

enum Tool: string
{
    case PHP_STAN = 'phpstan';
    case PHP_CS = 'phpcs';
    case PHP_MD = 'phpmd';
    case RECTOR = 'rector';
    case PHP_CS_FIXER = 'php-cs-fixer';
    case COMPOSER_UNUSED = 'composer-unused';
    case COMPOSER_NORMALIZE = 'composer-normalize';

    public function label(): string
    {
        return match ($this) {
            self::PHP_STAN           => 'PHPStan',
            self::PHP_CS             => 'PHPCS',
            self::PHP_MD             => 'PHPMD',
            self::RECTOR             => 'Rector',
            self::PHP_CS_FIXER       => 'PHP-CS-Fixer',
            self::COMPOSER_UNUSED    => 'composer-unused',
            self::COMPOSER_NORMALIZE => 'composer-normalize',
        };
    }

    public function generatedTarget(): ?string
    {
        return match ($this) {
            self::PHP_STAN           => 'phpstan.neon',
            self::PHP_CS             => 'phpcs.xml',
            self::PHP_MD             => 'phpmd.ruleset.xml',
            self::RECTOR             => 'rector.php',
            self::PHP_CS_FIXER       => '.php-cs-fixer.php',
            self::COMPOSER_UNUSED    => 'composer-unused.php',
            self::COMPOSER_NORMALIZE => null,
        };
    }

    public function requiresGeneration(): bool
    {
        return match ($this) {
            self::COMPOSER_NORMALIZE => false,
            default                  => true,
        };
    }
}
