<?php

declare(strict_types=1);

namespace Linters\Enum;

use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion as RectorPhpVersion;

enum PhpVersion: string
{
    case PHP_83 = '8.3';
    case PHP_84 = '8.4';
    case PHP_85 = '8.5';

    public function getRectorPhpVersion(): int
    {
        return match ($this) {
            self::PHP_83 => RectorPhpVersion::PHP_83,
            self::PHP_84 => RectorPhpVersion::PHP_84,
            self::PHP_85 => RectorPhpVersion::PHP_85,
        };
    }

    public function getLevelSetList(): string
    {
        return match ($this) {
            self::PHP_83 => LevelSetList::UP_TO_PHP_83,
            self::PHP_84 => LevelSetList::UP_TO_PHP_84,
            self::PHP_85 => LevelSetList::UP_TO_PHP_85,
        };
    }
}
