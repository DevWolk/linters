<?php

declare(strict_types=1);

namespace Linters\Rector\Set;

final class AppRectorSetList
{
    public const string APP_RULES = __DIR__ . '/../Configs/Sets/app-rules.php';

    public const string DOCTRINE = __DIR__ . '/../Configs/Sets/doctrine.php';

    public const string LARAVEL11 = __DIR__ . '/../Configs/Sets/laravel11.php';

    public const string LARAVEL12 = __DIR__ . '/../Configs/Sets/laravel12.php';

    public const string PHPUNIT11 = __DIR__ . '/../Configs/Sets/phpunit11.php';

    public const string PHPUNIT12 = __DIR__ . '/../Configs/Sets/phpunit12.php';
}
