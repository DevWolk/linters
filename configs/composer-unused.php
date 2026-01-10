<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;
use Linters\Utils\ConfigurationLoader;

return static function (Configuration $config): Configuration {
    $composerLoader = new ConfigurationLoader();

    $namedFilters = $composerLoader->get('composer-unused.named-filters', []);
    if (is_array($namedFilters)) {
        foreach ($namedFilters as $filter) {
            if (is_string($filter) && $filter !== '') {
                $config->addNamedFilter(NamedFilter::fromString($filter));
            }
        }
    }

    return $config;
};
