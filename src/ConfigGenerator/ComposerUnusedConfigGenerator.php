<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

use Linters\ConfigGenerator\Contracts\AbstractStubConfigGenerator;
use Linters\Enum\Tool;
use Linters\Utils\ConfigurationLoader;

final class ComposerUnusedConfigGenerator extends AbstractStubConfigGenerator
{
    public function __construct(ConfigurationLoader $loader)
    {
        parent::__construct(Tool::COMPOSER_UNUSED, $loader);
    }
}
