<?php

declare(strict_types=1);

namespace Linters\Console;

use Linters\Console\Command\GenerateConfigCommand;
use Linters\Console\Command\RunCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    public function __construct()
    {
        parent::__construct('Linters', '0.0.1');

        $this->add(new GenerateConfigCommand());
        $this->add(new RunCommand());
    }
}
