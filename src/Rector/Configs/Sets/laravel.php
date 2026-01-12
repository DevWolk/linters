<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    // Check if rector/rector-laravel is installed
    if (!class_exists(\RectorLaravel\Set\LaravelLevelSetList::class)) {

        return;
    }

    $rectorConfig->sets([
        \RectorLaravel\Set\LaravelLevelSetList::UP_TO_LARAVEL_110,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_110,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_CODE_QUALITY,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
    ]);
};
