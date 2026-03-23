<?php

declare(strict_types=1);

namespace Linters\CommandBuilder;

use Linters\CommandBuilder\Contracts\AbstractCommandBuilder;

/**
 * Uses 'composer normalize' directly instead of resolveBinary() because
 * composer-normalize is a Composer plugin, not a standalone vendor binary.
 */
final class ComposerNormalizeCommandBuilder extends AbstractCommandBuilder
{
    public function build(array $extraArgs): string
    {
        return 'composer normalize' . $this->buildExtraArgs($extraArgs);
    }
}
