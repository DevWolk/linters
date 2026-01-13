<?php

declare(strict_types=1);

namespace Linters\Rector\Set;

final class AppRectorSetList
{
    public const string APP_RULES = __DIR__ . '/../Configs/Sets/app-rules.php';

    public const string DOCTRINE = __DIR__ . '/../Configs/Sets/doctrine.php';

    public const string LARAVEL = __DIR__ . '/../Configs/Sets/laravel.php';
}
