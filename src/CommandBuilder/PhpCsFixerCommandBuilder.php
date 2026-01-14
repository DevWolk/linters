<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

final class PhpCsFixerCommandBuilder extends AbstractConfigurableCommandBuilder
{
    public function build(array $extraArgs): string
    {
        $command = $this->escapeArg($this->resolveBinary())
            . ' fix --config=' . $this->escapeArg($this->getConfigPath())
            . ' --allow-risky=yes';

        return $command . $this->buildExtraArgs($extraArgs);
    }
}
