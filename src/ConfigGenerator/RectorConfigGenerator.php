<?php

declare(strict_types=1);

namespace Linters\ConfigGenerator;

final class RectorConfigGenerator extends AbstractStubConfigGenerator
{
    protected function getConfigFileName(): string
    {
        return 'rector.php';
    }

    protected function getToolName(): string
    {
        return 'Rector';
    }

    protected function getConfigKey(): string
    {
        return 'rector';
    }

    protected function getDocumentationUrl(): string
    {
        return 'https://getrector.com/documentation';
    }
}
