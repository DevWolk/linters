<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Dbal211\Rector\MethodCall\ReplaceFetchAllMethodCallRector;
use Rector\Doctrine\Orm214\Rector\Param\ReplaceLifecycleEventArgsByDedicatedEventArgsRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rules([
        ReplaceFetchAllMethodCallRector::class,
        ReplaceLifecycleEventArgsByDedicatedEventArgsRector::class,
    ]);
};
