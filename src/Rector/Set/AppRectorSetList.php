<?php

declare(strict_types=1);

namespace Linters\Rector\Set;

final class AppRectorSetList
{
    /** @var string */
    public const APP_RULES = __DIR__ . '/../Configs/Sets/app-rules.php';

    /** @var string */
    public const STRICT_TYPES = __DIR__ . '/../Configs/Sets/strict-types.php';

    /** @var string */
    public const LARAVEL = __DIR__ . '/../Configs/Sets/laravel.php';

    /** @var string */
    public const SYMFONY = __DIR__ . '/../Configs/Sets/symfony.php';
}
