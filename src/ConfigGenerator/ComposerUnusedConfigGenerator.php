<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

final class ComposerUnusedConfigGenerator extends AbstractStubConfigGenerator
{
    protected function getConfigFileName(): string
    {
        return 'composer-unused.php';
    }

    protected function getToolName(): string
    {
        return 'composer-unused';
    }

    protected function getConfigKey(): string
    {
        return 'composer-unused';
    }

    protected function getDocumentationUrl(): string
    {
        return 'https://github.com/composer-unused/composer-unused';
    }
}
