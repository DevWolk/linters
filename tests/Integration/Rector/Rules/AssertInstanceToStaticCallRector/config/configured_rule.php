<?php

declare(strict_types=1);

use Linters\Rector\Rules\AssertInstanceToStaticCallRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rules([
        AssertInstanceToStaticCallRector::class,
    ]);
};
