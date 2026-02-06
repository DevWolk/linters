<?php

declare(strict_types=1);

namespace Linters\CommandBuilder\Contracts;

interface ConfigurableCommandBuilderInterface extends CommandBuilderInterface
{
    public function setConfigPath(string $configPath): void;
}
