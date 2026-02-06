<?php

declare(strict_types=1);

namespace Linters\CommandBuilder\Contracts;

interface CommandBuilderInterface
{
    /**
     * @param string[] $extraArgs
     */
    public function build(array $extraArgs): string;
}
