<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

final class PhpCsFixerConfigGenerator extends AbstractStubConfigGenerator
{
    protected function getConfigFileName(): string
    {
        return '.php-cs-fixer.dist.php';
    }

    protected function getToolName(): string
    {
        return 'PHP-CS-Fixer';
    }

    protected function getConfigKey(): string
    {
        return 'php-cs-fixer';
    }

    protected function getDocumentationUrl(): string
    {
        return 'https://cs.symfony.com/doc/usage.html';
    }
}
