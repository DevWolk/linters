<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;
use Linters\Utils\ConfigurationLoader;

return static function (Configuration $config): Configuration {
    $composerLoader = new ConfigurationLoader();

    foreach ($composerLoader->getComposerUnusedConfig()->namedFilters as $filter) {
        $config->addNamedFilter(NamedFilter::fromString($filter));
    }

    return $config;
};
