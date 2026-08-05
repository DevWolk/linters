<?php

declare(strict_types=1);

namespace Linters\Service;

use Linters\CommandBuilder\ComposerNormalizeCommandBuilder;
use Linters\CommandBuilder\ComposerUnusedCommandBuilder;
use Linters\CommandBuilder\Contracts\CommandBuilderInterface;
use Linters\CommandBuilder\PhpCsCommandBuilder;
use Linters\CommandBuilder\PhpCsFixerCommandBuilder;
use Linters\CommandBuilder\PhpMdCommandBuilder;
use Linters\CommandBuilder\PhpStanCommandBuilder;
use Linters\CommandBuilder\RectorCommandBuilder;
use Linters\Enum\Tool;
use Linters\Utils\ConfigurationLoader;

final readonly class CommandBuilderFactory
{
    public function __construct(private ConfigurationLoader $loader)
    {
    }

    public function create(Tool $tool): CommandBuilderInterface
    {
        return match ($tool) {
            Tool::PHP_STAN => new PhpStanCommandBuilder($tool, $this->loader),
            Tool::PHP_CS, Tool::PHP_CBF => new PhpCsCommandBuilder($tool, $this->loader),
            Tool::PHP_MD => new PhpMdCommandBuilder($tool, $this->loader),
            Tool::RECTOR => new RectorCommandBuilder($tool, $this->loader),
            Tool::PHP_CS_FIXER => new PhpCsFixerCommandBuilder($tool, $this->loader),
            Tool::COMPOSER_UNUSED => new ComposerUnusedCommandBuilder($tool, $this->loader),
            Tool::COMPOSER_NORMALIZE => new ComposerNormalizeCommandBuilder($tool, $this->loader),
        };
    }
}
