<?php

declare(strict_types=1);

namespace Linters\Console\Command;

use Linters\Enum\Tool;
use Linters\Service\ToolRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateConfigCommand extends AbstractToolCommand
{
    protected function getCommandName(): string
    {
        return 'generate';
    }

    protected function getCommandDescription(): string
    {
        return 'Generate tool configuration from extra.linters';
    }

    protected function doExecute(Tool $tool, ToolRunner $runner, OutputInterface $output): int
    {
        $target = $runner->generate($tool);
        $output->writeln('Generated: ' . $target);

        return Command::SUCCESS;
    }
}
