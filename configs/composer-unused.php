<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

return static function (Configuration $config): Configuration {
    // Add the "wikimedia/composer-merge-plugin" named filter via composer extra configuration
    $config
         ->addNamedFilter(NamedFilter::fromString('wikimedia/composer-merge-plugin'));

    return $config;
};
