<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    // Check if https://github.com/rectorphp/rector-symfony is installed
    if (!class_exists(SymfonySetList::class)) {
        return;
    }

    $rectorConfig->sets([
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        SymfonySetList::CONFIGS,
    ]);
};
