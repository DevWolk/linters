<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

interface ConfigGeneratorInterface
{
    public function generate(string $targetPath): void;
}
