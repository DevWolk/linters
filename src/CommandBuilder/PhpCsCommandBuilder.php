<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

final class PhpCsCommandBuilder extends AbstractConfigurableCommandBuilder
{
    public function build(array $extraArgs): string
    {
        $command = $this->escapeArg($this->resolveBinary())
            . ' --standard=' . $this->escapeArg($this->getConfigPath());

        $config = $this->loader->getPhpCsConfig();
        $parallel = $config->parallel;

        if ($parallel?->enabled === true && $parallel->maxProcesses !== null) {
            $command .= ' --parallel=' . $parallel->maxProcesses;
        }

        return $command . $this->buildExtraArgs($extraArgs);
    }
}
