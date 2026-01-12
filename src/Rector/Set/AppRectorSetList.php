<?php

declare(strict_types=1);

namespace Linters\Rector\Set;

final class AppRectorSetList
{
    /** @var string */
    public const APP_RULES = __DIR__ . '/../Configs/Sets/app-rules.php';

    /** @var string */
    public const DOCTRINE = __DIR__ . '/../Configs/Sets/doctrine.php';

    /** @var string */
    public const LARAVEL = __DIR__ . '/../Configs/Sets/laravel.php';
}
