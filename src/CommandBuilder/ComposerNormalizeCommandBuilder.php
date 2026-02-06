<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

use Linters\CommandBuilder\Contracts\AbstractCommandBuilder;

final class ComposerNormalizeCommandBuilder extends AbstractCommandBuilder
{
    public function build(array $extraArgs): string
    {
        return 'composer normalize' . $this->buildExtraArgs($extraArgs);
    }
}
