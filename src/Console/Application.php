<?php

declare(strict_types=1);

namespace Linters\Console;

use Composer\InstalledVersions;
use Linters\Console\Command\GenerateConfigCommand;
use Linters\Console\Command\RunCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    private const PACKAGE_NAME = 'devwolk/linters';

    public function __construct()
    {
        parent::__construct('Linters', self::getVersionFromComposerJson());

        $this->add(new GenerateConfigCommand());
        $this->add(new RunCommand());
    }

    private static function getVersionFromComposerJson(): string
    {
        if (!class_exists(InstalledVersions::class)) {
            return 'UNKNOWN';
        }

        return InstalledVersions::getPrettyVersion(self::PACKAGE_NAME) ?? 'UNKNOWN';
    }
}
