<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

use Linters\CommandBuilder\Contracts\AbstractConfigurableCommandBuilder;

final class PhpStanCommandBuilder extends AbstractConfigurableCommandBuilder
{
    public function build(array $extraArgs): string
    {
        $command = $this->escapeArg($this->resolveBinary())
            . ' analyze --configuration=' . $this->escapeArg($this->getConfigPath());

        return $command . $this->buildExtraArgs($extraArgs);
    }
}
