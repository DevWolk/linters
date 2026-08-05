<?php

declare(strict_types=1);

namespace Linters\Enum;

use Linters\Rector\Set\AppRectorSetList;

enum RectorSet: string
{
    case LARAVEL11 = 'laravel11';
    case LARAVEL12 = 'laravel12';
    case PHPUNIT11 = 'phpunit11';
    case PHPUNIT12 = 'phpunit12';
    case PHPUNIT13 = 'phpunit13';
    case DOCTRINE = 'doctrine';
    case SYMFONY = 'symfony';

    public function getPath(): string
    {
        return match ($this) {
            self::LARAVEL11 => AppRectorSetList::LARAVEL11,
            self::LARAVEL12 => AppRectorSetList::LARAVEL12,
            self::DOCTRINE => AppRectorSetList::DOCTRINE,
            self::PHPUNIT11 => AppRectorSetList::PHPUNIT11,
            self::PHPUNIT12 => AppRectorSetList::PHPUNIT12,
            self::PHPUNIT13 => AppRectorSetList::PHPUNIT13,
            self::SYMFONY => AppRectorSetList::SYMFONY,
        };
    }
}
