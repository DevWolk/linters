<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Dbal211\Rector\MethodCall\ReplaceFetchAllMethodCallRector;
use Rector\Doctrine\Orm214\Rector\Param\ReplaceLifecycleEventArgsByDedicatedEventArgsRector;
use Rector\Doctrine\Set\DoctrineSetList;

return static function (RectorConfig $rectorConfig): void {
    if (class_exists(DoctrineSetList::class) === false) {
        return;
    }

    $rectorConfig->rules([
        ReplaceFetchAllMethodCallRector::class,
        ReplaceLifecycleEventArgsByDedicatedEventArgsRector::class,
    ]);

    $rectorConfig->sets([
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::GEDMO_ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::MONGODB__ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
    ]);
};
