<?php

declare(strict_types=1);

namespace Linters\Console\Command;

use Linters\Enum\Tool;
use Linters\Service\ToolRunner;
use Linters\Utils\ConfigurationLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

abstract class AbstractToolCommand extends Command
{
    abstract protected function getCommandName(): string;

    abstract protected function getCommandDescription(): string;

    abstract protected function doExecute(Tool $tool, ToolRunner $runner, OutputInterface $output): int;

    protected function configure(): void
    {
        $tools = implode('|', array_map(
            static fn (Tool $tool): string => $tool->value,
            Tool::cases(),
        ));

        $this
            ->setName($this->getCommandName())
            ->setDescription($this->getCommandDescription())
            ->addArgument('tool', InputArgument::REQUIRED, $tools);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $toolName = strtolower((string) $input->getArgument('tool'));
            $tool = Tool::from($toolName);

            $loader = new ConfigurationLoader();
            $runner = new ToolRunner($loader);

            return $this->doExecute($tool, $runner, $output);
        } catch (Throwable $throwable) {
            $output->writeln('<error>' . $throwable->getMessage() . '</error>');

            if ($output->isVerbose()) {
                $output->writeln('<error>' . $throwable->getTraceAsString() . '</error>');
            }

            return Command::FAILURE;
        }
    }
}
