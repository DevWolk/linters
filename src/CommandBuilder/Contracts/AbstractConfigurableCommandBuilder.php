<?php

declare(strict_types=1);

namespace Linters\CommandBuilder\Contracts;

use RuntimeException;

abstract class AbstractConfigurableCommandBuilder extends AbstractCommandBuilder implements ConfigurableCommandBuilderInterface
{
    protected ?string $configPath = null;

    public function setConfigPath(string $configPath): void
    {
        $this->configPath = $configPath;
    }

    protected function getConfigPath(): string
    {
        if ($this->configPath === null) {
            throw new RuntimeException($this->tool->label() . ' requires a config path');
        }

        return $this->configPath;
    }
}
