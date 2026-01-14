<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

final class ComposerNormalizeCommandBuilder extends AbstractCommandBuilder
{
    public function build(array $extraArgs): string
    {
        return 'composer normalize' . $this->buildExtraArgs($extraArgs);
    }
}
