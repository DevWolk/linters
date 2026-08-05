<?php

declare(strict_types=1);

namespace Linters\Service;

use Linters\ConfigGenerator\ComposerUnusedConfigGenerator;
use Linters\ConfigGenerator\Contracts\ConfigGeneratorInterface;
use Linters\ConfigGenerator\PhpCsConfigGenerator;
use Linters\ConfigGenerator\PhpCsFixerConfigGenerator;
use Linters\ConfigGenerator\PhpMdConfigGenerator;
use Linters\ConfigGenerator\PhpStanConfigGenerator;
use Linters\ConfigGenerator\RectorConfigGenerator;
use Linters\Enum\Tool;
use Linters\Utils\ConfigurationLoader;
use RuntimeException;

final readonly class ConfigGeneratorFactory
{
    public function __construct(private ConfigurationLoader $loader)
    {
    }

    public function create(Tool $tool): ConfigGeneratorInterface
    {
        return match ($tool) {
            Tool::PHP_STAN => new PhpStanConfigGenerator($this->loader),
            Tool::PHP_CS, Tool::PHP_CBF => new PhpCsConfigGenerator($this->loader),
            Tool::PHP_MD => new PhpMdConfigGenerator($this->loader),
            Tool::RECTOR => new RectorConfigGenerator($this->loader),
            Tool::PHP_CS_FIXER => new PhpCsFixerConfigGenerator($this->loader),
            Tool::COMPOSER_UNUSED => new ComposerUnusedConfigGenerator($this->loader),
            Tool::COMPOSER_NORMALIZE => throw new RuntimeException('No generator for this tool'),
        };
    }
}
