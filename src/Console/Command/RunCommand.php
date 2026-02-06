<?php

declare(strict_types=1);

namespace Linters\Console\Command;

use Linters\Console\Command\Contracts\AbstractToolCommand;
use Linters\Enum\Tool;
use Linters\Service\ToolRunner;
use Symfony\Component\Console\Output\OutputInterface;

final class RunCommand extends AbstractToolCommand
{
    protected function getCommandName(): string
    {
        return 'run';
    }

    protected function getCommandDescription(): string
    {
        return 'Generate config and run a tool from extra.linters';
    }

    protected function doExecute(
        Tool $tool,
        ToolRunner $runner,
        OutputInterface $output,
    ): int {
        return $runner->run($tool, $output, $this->getExtraArgs());
    }
}
