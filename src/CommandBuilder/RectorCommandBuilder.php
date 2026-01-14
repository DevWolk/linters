<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

final class RectorCommandBuilder extends AbstractConfigurableCommandBuilder
{
    public function build(array $extraArgs): string
    {
        $command = $this->escapeArg($this->resolveBinary())
            . ' process --config=' . $this->escapeArg($this->getConfigPath())
            . ' --clear-cache';

        return $command . $this->buildExtraArgs($extraArgs);
    }
}
