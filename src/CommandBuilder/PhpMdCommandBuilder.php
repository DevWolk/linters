<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

use Linters\CommandBuilder\Contracts\AbstractConfigurableCommandBuilder;
use Linters\Utils\ConfigValidation;

final class PhpMdCommandBuilder extends AbstractConfigurableCommandBuilder
{
    private const string DEFAULT_FORMAT = 'text';

    public function build(array $extraArgs): string
    {
        $config = $this->loader->getPhpMdConfig();

        $command = $this->escapeArg($this->resolveBinary())
            . ' ' . $this->escapeArg(implode(',', $config->paths))
            . ' ' . $this->escapeArg(self::DEFAULT_FORMAT)
            . ' ' . $this->escapeArg($this->getConfigPath());

        $baseline = $config->baseline;

        if (ConfigValidation::isNonEmptyString($baseline)) {
            $command .= ' --baseline-file=' . $this->escapeArg($baseline);
        }

        return $command . $this->buildExtraArgs($extraArgs);
    }
}
