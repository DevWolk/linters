<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

use Linters\CommandBuilder\Contracts\AbstractConfigurableCommandBuilder;

final class ComposerUnusedCommandBuilder extends AbstractConfigurableCommandBuilder
{
    public function build(array $extraArgs): string
    {
        $command = $this->escapeArg($this->resolveBinary())
            . ' --configuration=' . $this->escapeArg($this->getConfigPath());

        return $command . $this->buildExtraArgs($extraArgs);
    }
}
