<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator\Contracts;

interface ConfigGeneratorInterface
{
    public function generate(string $targetPath): void;
}
