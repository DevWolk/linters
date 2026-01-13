<?php

declare(strict_types=1);

namespace Linters\Console\Command;

use Linters\Enum\Tool;
use Linters\Service\ToolRunner;
use Safe\Exceptions\DirException;
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

    /**
     * @throws DirException
     */
    protected function doExecute(Tool $tool, ToolRunner $runner, OutputInterface $output): int
    {
        return $runner->run($tool, $output);
    }
}
