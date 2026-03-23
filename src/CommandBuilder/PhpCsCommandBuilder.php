<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

use Linters\CommandBuilder\Contracts\AbstractConfigurableCommandBuilder;

final class PhpCsCommandBuilder extends AbstractConfigurableCommandBuilder
{
    public function build(array $extraArgs): string
    {
        $command = $this->escapeArg($this->resolveBinary())
            . ' --standard=' . $this->escapeArg($this->getConfigPath());

        $config = $this->loader->getPhpCsConfig();
        $parallel = $config->parallel;

        if ($parallel?->enabled === true) {
            $command .= $parallel->maxProcesses !== null
                ? ' --parallel=' . $parallel->maxProcesses
                : ' --parallel';
        }

        return $command . $this->buildExtraArgs($extraArgs);
    }
}
