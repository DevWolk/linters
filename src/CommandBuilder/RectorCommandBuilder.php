<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

use Linters\CommandBuilder\Contracts\AbstractConfigurableCommandBuilder;

final class RectorCommandBuilder extends AbstractConfigurableCommandBuilder
{
    public function build(array $extraArgs): string
    {
        $command = $this->escapeArg($this->resolveBinary())
            . ' process --config=' . $this->escapeArg($this->getConfigPath());

        $config = $this->loader->getRectorConfig();

        if ($config->clearCache) {
            $command .= ' --clear-cache';
        }

        return $command . $this->buildExtraArgs($extraArgs);
    }
}
